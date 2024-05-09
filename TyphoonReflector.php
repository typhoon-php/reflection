<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Psr\SimpleCache\CacheInterface;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassConstantId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\DeclarationId;
use Typhoon\DeclarationId\DeclarationIdMap;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\DeclarationId\PropertyId;
use Typhoon\PhpStormReflectionStubs\PhpStormStubsLocator;
use Typhoon\Reflection\Cache\InMemoryCache;
use Typhoon\Reflection\Exception\ClassDoesNotExist;
use Typhoon\Reflection\Internal\CleanUp;
use Typhoon\Reflection\Internal\CompleteEnum;
use Typhoon\Reflection\Internal\CopyPromotedParametersToProperties;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\DataStorage;
use Typhoon\Reflection\Internal\EnsureInterfaceMethodsAreAbstract;
use Typhoon\Reflection\Internal\EnsureReadonlyClassPropertiesAreReadonly;
use Typhoon\Reflection\Internal\PhpParserReflector\FindTopLevelDeclarationsVisitor;
use Typhoon\Reflection\Internal\PhpParserReflector\FixNodeStartLineVisitor;
use Typhoon\Reflection\Internal\PhpParserReflector\ReflectPhpParserNode;
use Typhoon\Reflection\Internal\PhpParserReflector\SetTypeContextVisitor;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\Reflection\Internal\ReflectionHooks;
use Typhoon\Reflection\Internal\ResolveAttributesRepeated;
use Typhoon\Reflection\Internal\ResolveClassInheritance;
use Typhoon\Reflection\Internal\ResolvedResource;
use Typhoon\Reflection\Internal\ResolveParametersIndex;
use Typhoon\Reflection\Internal\TypeContext\TypeContextVisitor;
use Typhoon\Reflection\Locator\ComposerLocator;
use Typhoon\Reflection\Locator\Locators;
use Typhoon\Reflection\Locator\NativeReflectionClassLocator;
use Typhoon\Reflection\Locator\NativeReflectionFunctionLocator;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\classId;

/**
 * @api
 */
final class TyphoonReflector implements Reflector
{
    private function __construct(
        private readonly Parser $phpParser,
        private readonly Locator $locator,
        private readonly DataStorage $storage,
    ) {}

    /**
     * @param ?list<Locator> $locators
     */
    public static function build(
        ?array $locators = null,
        CacheInterface $cache = new InMemoryCache(),
        ?Parser $phpParser = null,
    ): self {
        return new self(
            phpParser: $phpParser ?? (new ParserFactory())->createForHostVersion(),
            locator: new Locators($locators ?? self::defaultLocators()),
            storage: new DataStorage($cache),
        );
    }

    /**
     * @return list<Locator>
     */
    public static function defaultLocators(): array
    {
        $locators = [];

        if (class_exists(PhpStormStubsLocator::class)) {
            $locators[] = new PhpStormStubsLocator();
        }

        if (ComposerLocator::isSupported()) {
            $locators[] = new ComposerLocator();
        }

        $locators[] = new NativeReflectionClassLocator();
        $locators[] = new NativeReflectionFunctionLocator();

        return $locators;
    }

    /**
     * @template T of object
     * @param string|class-string<T>|T $nameOrObject
     * @return ClassReflection<T>
     */
    public function reflectClass(string|object $nameOrObject): ClassReflection
    {
        /** @var ClassReflection<T> */
        return $this->reflect(classId($nameOrObject));
    }

    /**
     * @psalm-suppress MixedInferredReturnType, MixedReturnStatement, UndefinedMethod
     */
    public function reflect(DeclarationId $id): Reflection
    {
        return match (true) {
            $id instanceof ClassId, $id instanceof AnonymousClassId => new ClassReflection(
                id: $id,
                data: $this->reflectData($id) ?? throw new ClassDoesNotExist($id->name),
                reflector: $this,
            ),
            $id instanceof PropertyId => $this->reflect($id->class)->property($id->name) ?? throw new \LogicException('Does not exist'),
            $id instanceof ClassConstantId => $this->reflect($id->class)->constant($id->name) ?? throw new \LogicException('Does not exist'),
            $id instanceof MethodId => $this->reflect($id->class)->method($id->name) ?? throw new \LogicException('Does not exist'),
            $id instanceof ParameterId => $this->reflect($id->function)->parameter($id->name) ?? throw new \LogicException('Does not exist'),
            default => throw new \LogicException($id->toString() . ' not supported yet'),
        };
    }

    /**
     * @return DeclarationIdMap<ClassId|AnonymousClassId, ClassReflection>
     */
    public function reflectCode(string $code, TypedMap $baseData = new TypedMap()): DeclarationIdMap
    {
        $finder = new FindTopLevelDeclarationsVisitor();
        $this->traverse($this->parse($code), $finder);
        $this->stageForCommit($finder->nodes, $baseData);

        /** @var DeclarationIdMap<ClassId|AnonymousClassId, ClassReflection> */
        $reflections = new DeclarationIdMap();

        foreach ($finder->nodes as $declarationId => $_) {
            $reflections = $reflections->with($declarationId, $this->reflect($declarationId));
        }

        $this->storage->rollback();

        return $reflections;
    }

    private function reflectData(ClassId|AnonymousClassId $id): ?TypedMap
    {
        $cachedData = $this->storage->get($id);

        if ($cachedData !== null) {
            return $cachedData;
        }

        if ($id instanceof AnonymousClassId) {
            $resolvedResource = ResolvedResource::fromAnonymousClassId($id);
        } else {
            $resource = $this->locator->locate($id);

            if ($resource === null) {
                return null;
            }

            $resolvedResource = ResolvedResource::fromResource($resource);
        }

        $finder = new FindTopLevelDeclarationsVisitor();
        $this->traverse($this->parse($resolvedResource->code), $finder, $resolvedResource->file);
        $this->stageForCommit($finder->nodes, $resolvedResource->baseData, $resolvedResource->hooks);

        $data = $this->storage->get($id);

        $this->storage->commit();

        return $data;
    }

    /**
     * @return array<Node>
     */
    private function parse(string $code): array
    {
        return $this->phpParser->parse($code) ?? throw new \LogicException();
    }

    /**
     * @param ?non-empty-string $file
     * @param array<Node> $nodes
     */
    private function traverse(array $nodes, NodeVisitor $visitor, ?string $file = null): void
    {
        $traverser = new NodeTraverser();

        $nameResolver = new NameResolver();
        $typeContextVisitor = new TypeContextVisitor($nameResolver->getNameContext(), file: $file);
        $traverser->addVisitor(new FixNodeStartLineVisitor($this->phpParser->getTokens()));
        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($typeContextVisitor);
        $traverser->addVisitor(new SetTypeContextVisitor($typeContextVisitor));
        $traverser->addVisitor($visitor);

        $traverser->traverse($nodes);
    }

    /**
     * @param DeclarationIdMap<ClassId|AnonymousClassId, ClassLike> $nodes
     * @param list<ReflectionHook> $hooks
     */
    private function stageForCommit(DeclarationIdMap $nodes, TypedMap $baseData = new TypedMap(), array $hooks = []): void
    {
        $hook = $this->buildHooks($hooks);

        foreach ($nodes as $declarationId => $node) {
            $data = $baseData->with(Data::Node(), $node);
            $this->storage->stageForCommit($declarationId, static fn(): TypedMap => $hook->reflect($declarationId, $data));
        }
    }

    /**
     * @param list<ReflectionHook> $hooks
     */
    private function buildHooks(array $hooks = []): ReflectionHook
    {
        return new ReflectionHooks([
            new ReflectPhpParserNode(),
            ...$hooks,
            new CopyPromotedParametersToProperties(),
            new CompleteEnum(),
            new EnsureInterfaceMethodsAreAbstract(),
            new EnsureReadonlyClassPropertiesAreReadonly(),
            new ResolveAttributesRepeated(),
            new ResolveParametersIndex(),
            new ResolveClassInheritance($this),
            new CleanUp(),
        ]);
    }
}
