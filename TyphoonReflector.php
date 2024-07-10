<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Psr\SimpleCache\CacheInterface;
use Typhoon\ChangeDetector\FileChangeDetector;
use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassConstantId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\DeclarationId\PropertyId;
use Typhoon\DeclarationId\TemplateId;
use Typhoon\PhpStormReflectionStubs\PhpStormStubsLocator;
use Typhoon\Reflection\Cache\InMemoryCache;
use Typhoon\Reflection\Exception\ClassDoesNotExist;
use Typhoon\Reflection\Exception\FileNotReadable;
use Typhoon\Reflection\Internal\ClassKind;
use Typhoon\Reflection\Internal\CompleteReflection\AddStringableInterface;
use Typhoon\Reflection\Internal\CompleteReflection\CleanUp;
use Typhoon\Reflection\Internal\CompleteReflection\CompleteEnumReflection;
use Typhoon\Reflection\Internal\CompleteReflection\CopyPromotedParametersToProperties;
use Typhoon\Reflection\Internal\CompleteReflection\EnsureInterfaceMethodsAreAbstract;
use Typhoon\Reflection\Internal\CompleteReflection\EnsureReadonlyClassPropertiesAreReadonly;
use Typhoon\Reflection\Internal\CompleteReflection\ResolveAttributesRepeated;
use Typhoon\Reflection\Internal\CompleteReflection\ResolveChangeDetector;
use Typhoon\Reflection\Internal\CompleteReflection\ResolveParametersIndex;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Expression\ExpressionCompilerVisitor;
use Typhoon\Reflection\Internal\PhpParserReflector\FixNodeStartLineVisitor;
use Typhoon\Reflection\Internal\PhpParserReflector\PhpParserReflector;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\Reflection\Internal\ReflectionHooks;
use Typhoon\Reflection\Internal\ReflectPhpDocTypes\ReflectPhpDocTypes;
use Typhoon\Reflection\Internal\ResolveClassInheritance\ResolveClassInheritance;
use Typhoon\Reflection\Internal\Storage\DataStorage;
use Typhoon\Reflection\Internal\TypeContext\TypeContextVisitor;
use Typhoon\Reflection\Locator\ComposerLocator;
use Typhoon\Reflection\Locator\Locators;
use Typhoon\Reflection\Locator\NativeReflectionClassLocator;
use Typhoon\Reflection\Locator\NativeReflectionFunctionLocator;
use Typhoon\TypedMap\TypedMap;

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

    public function classExists(string $class): bool
    {
        try {
            $this->reflectClassLike($class);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @template T of object
     * @param string|class-string<T>|T $nameOrObject
     * @return ClassLikeReflection<T, NamedClassId|AnonymousClassId>
     */
    public function reflectClassLike(string|object $nameOrObject): ClassLikeReflection
    {
        /** @var ClassLikeReflection<T> */
        return $this->reflect(Id::class($nameOrObject));
    }

    /**
     * @template T of object
     * @param string|class-string<T>|T $nameOrObject
     * @return ClassReflection<T>
     */
    public function reflectClass(string|object $nameOrObject): ClassReflection
    {
        $reflection = $this->reflect(Id::namedClass($nameOrObject));

        if (!$reflection instanceof ClassReflection) {
            throw new \RuntimeException('Not a class!');
        }

        /** @var ClassReflection<T> */
        return $reflection;
    }

    /**
     * @template T of object
     * @param string|class-string<T> $name
     * @return InterfaceReflection<T>
     */
    public function reflectInterface(string $name): InterfaceReflection
    {
        $reflection = $this->reflect(Id::namedClass($name));

        if (!$reflection instanceof InterfaceReflection) {
            throw new \RuntimeException('Not an interface!');
        }

        /** @var InterfaceReflection<T> */
        return $reflection;
    }

    /**
     * @template T of object
     * @param string|class-string<T> $name
     * @return TraitReflection<T>
     */
    public function reflectTrait(string $name): TraitReflection
    {
        $reflection = $this->reflect(Id::namedClass($name));

        if (!$reflection instanceof TraitReflection) {
            throw new \RuntimeException('Not a trait!');
        }

        /** @var TraitReflection<T> */
        return $reflection;
    }

    /**
     * @template T of \UnitEnum
     * @param string|class-string<T> $name
     * @return EnumReflection<T>
     */
    public function reflectEnum(string $name): EnumReflection
    {
        $reflection = $this->reflect(Id::namedClass($name));

        if (!$reflection instanceof EnumReflection) {
            throw new \RuntimeException('Not an enum!');
        }

        /** @var EnumReflection<T> */
        return $reflection;
    }

    /**
     * @return (
     *     $id is NamedClassId ? ClassReflection|InterfaceReflection|TraitReflection|EnumReflection :
     *     $id is AnonymousClassId ? AnonymousClassReflection :
     *     $id is ClassConstantId ? ClassConstantReflection :
     *     $id is PropertyId ? PropertyReflection :
     *     $id is MethodId ? MethodReflection :
     *     $id is ParameterId ? ParameterReflection :
     *     $id is AliasId ? AliasReflection :
     *     $id is TemplateId ? TemplateReflection :
     *     never
     * )
     */
    public function reflect(Id $id): Reflection
    {
        if ($id instanceof NamedClassId) {
            $data = $this->reflectData($id) ?? throw new ClassDoesNotExist($id->name);

            return match ($data[Data::ClassKind]) {
                ClassKind::Class_ => new ClassReflection($id, $data, $this),
                ClassKind::Interface => new InterfaceReflection($id, $data, $this),
                ClassKind::Trait => new TraitReflection($id, $data, $this),
                ClassKind::Enum => new EnumReflection($id, $data, $this),
            };
        }

        return match (true) {
            $id instanceof AnonymousClassId => new AnonymousClassReflection(
                id: $id,
                data: $this->reflectData($id) ?? throw new ClassDoesNotExist($id->name ?? $id->toString()),
                reflector: $this,
            ),
            $id instanceof PropertyId => $this->reflect($id->class)->properties()[$id->name],
            $id instanceof ClassConstantId => $this->reflect($id->class)->constants()[$id->name],
            $id instanceof MethodId => $this->reflect($id->class)->methods()[$id->name],
            $id instanceof ParameterId => $this->reflect($id->function)->parameters()[$id->name],
            $id instanceof AliasId => $this->reflect($id->class)->aliases()[$id->name],
            $id instanceof TemplateId => $this->reflect($id->declaredAt)->templates()[$id->name],
            default => throw new \LogicException($id->toString() . ' not supported yet'),
        };
    }

    private function reflectData(NamedClassId|AnonymousClassId $id): ?TypedMap
    {
        $cachedData = $this->storage->get($id);

        if ($cachedData !== null) {
            return $cachedData;
        }

        \assert($id instanceof NamedClassId);
        $resource = $this->locator->locate($id);

        if ($resource === null) {
            return null;
        }

        $code = self::readFile($resource->file);
        $nodes = $this->phpParser->parse($code) ?? throw new \LogicException();

        $baseData = $resource->baseData;

        if (!$baseData[Data::InternallyDefined]) {
            $baseData = $baseData->set(Data::File, $resource->file);
        }

        if (!isset($baseData[Data::UnresolvedChangeDetectors])) {
            $baseData = $baseData->set(Data::UnresolvedChangeDetectors, [
                FileChangeDetector::fromFileAndContents($resource->file, $code),
            ]);
        }

        $traverser = new NodeTraverser();
        $nameResolver = new NameResolver();
        $typeContextVisitor = new TypeContextVisitor(
            nameContext: $nameResolver->getNameContext(),
            reader: new ReflectPhpDocTypes(),
            code: $code,
            file: $resource->file,
        );
        $expressionCompilerVisitor = new ExpressionCompilerVisitor($resource->file);
        $reflector = new PhpParserReflector($typeContextVisitor, $expressionCompilerVisitor);
        $traverser->addVisitor(new FixNodeStartLineVisitor($this->phpParser->getTokens()));
        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($typeContextVisitor);
        $traverser->addVisitor($expressionCompilerVisitor);
        $traverser->addVisitor($reflector);
        $traverser->traverse($nodes);

        foreach ($reflector->data as $declarationId => $data) {
            $this->storage->stageForCommit(
                $declarationId,
                fn(): TypedMap => $this->buildHooks($resource->hooks)->reflect($declarationId, $baseData->merge($data)),
            );
        }

        $data = $this->storage->get($id);

        $this->storage->commit();

        return $data;
    }

    /**
     * @param list<ReflectionHook> $hooks
     */
    private function buildHooks(array $hooks = []): ReflectionHook
    {
        return new ReflectionHooks([
            ...$hooks,
            new ReflectPhpDocTypes(),
            new CopyPromotedParametersToProperties(),
            new CompleteEnumReflection(),
            new AddStringableInterface(),
            new ResolveClassInheritance($this),
            new EnsureInterfaceMethodsAreAbstract(),
            new EnsureReadonlyClassPropertiesAreReadonly(),
            new ResolveAttributesRepeated(),
            new ResolveParametersIndex(),
            new ResolveChangeDetector(),
            new CleanUp(),
        ]);
    }

    /**
     * @psalm-assert non-empty-string $file
     */
    private static function readFile(string $file): string
    {
        $code = @file_get_contents($file);

        if ($code === false) {
            throw new FileNotReadable($file);
        }

        return $code;
    }
}
