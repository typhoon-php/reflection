<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PhpParser\Parser;
use PhpParser\ParserFactory;
use Psr\SimpleCache\CacheInterface;
use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassConstantId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\Internal\IdMap;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\DeclarationId\PropertyId;
use Typhoon\DeclarationId\TemplateId;
use Typhoon\PhpStormReflectionStubs\PhpStormStubsLocator;
use Typhoon\Reflection\Cache\InMemoryCache;
use Typhoon\Reflection\Exception\DeclarationNotFound;
use Typhoon\Reflection\Internal\Cache;
use Typhoon\Reflection\Internal\CompleteReflection\CleanUpInternallyDefined;
use Typhoon\Reflection\Internal\CompleteReflection\CompleteEnum;
use Typhoon\Reflection\Internal\CompleteReflection\CopyPromotedParametersToProperties;
use Typhoon\Reflection\Internal\CompleteReflection\RemoveCode;
use Typhoon\Reflection\Internal\CompleteReflection\RemoveContext;
use Typhoon\Reflection\Internal\CompleteReflection\SetAttributesRepeated;
use Typhoon\Reflection\Internal\CompleteReflection\SetInterfaceMethodsAbstract;
use Typhoon\Reflection\Internal\CompleteReflection\SetParametersIndexes;
use Typhoon\Reflection\Internal\CompleteReflection\SetReadonlyClassPropertiesReadonly;
use Typhoon\Reflection\Internal\CompleteReflection\SetStringableInterface;
use Typhoon\Reflection\Internal\CompleteReflection\SetTemplatesIndexes;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Hooks;
use Typhoon\Reflection\Internal\Inheritance\ResolveClassInheritance;
use Typhoon\Reflection\Internal\Locators;
use Typhoon\Reflection\Internal\PhpDoc\PhpDocReflector;
use Typhoon\Reflection\Internal\PhpParser\CodeReflector;
use Typhoon\Reflection\Internal\PhpParser\NodeReflector;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Reflection\Locator\AnonymousLocator;
use Typhoon\Reflection\Locator\ComposerLocator;
use Typhoon\Reflection\Locator\ConstantLocator;
use Typhoon\Reflection\Locator\DontAutoloadClassLocator;
use Typhoon\Reflection\Locator\FileAnonymousLocator;
use Typhoon\Reflection\Locator\NamedClassLocator;
use Typhoon\Reflection\Locator\NamedFunctionLocator;
use Typhoon\Reflection\Locator\NativeReflectionClassLocator;
use Typhoon\Reflection\Locator\NativeReflectionFunctionLocator;
use Typhoon\Reflection\Locator\Resource;
use Typhoon\Reflection\Locator\ScannedResourceLocator;

/**
 * @api
 */
final class TyphoonReflector
{
    private const BUFFER_SIZE = 100;

    /**
     * @param ?list<ConstantLocator|NamedFunctionLocator|NamedClassLocator|AnonymousLocator> $locators
     */
    public static function build(
        ?array $locators = null,
        CacheInterface $cache = new InMemoryCache(),
        ?Parser $phpParser = null,
    ): self {
        $phpDocReflector = new PhpDocReflector();

        return new self(
            codeReflector: new CodeReflector(
                phpParser: $phpParser ?? (new ParserFactory())->createForHostVersion(),
                annotatedTypesDriver: $phpDocReflector,
                nodeReflector: new NodeReflector(),
            ),
            locators: new Locators($locators ?? self::defaultLocators()),
            hooks: new Hooks([
                $phpDocReflector,
                CopyPromotedParametersToProperties::Instance,
                CompleteEnum::Instance,
                SetStringableInterface::Instance,
                SetInterfaceMethodsAbstract::Instance,
                SetReadonlyClassPropertiesReadonly::Instance,
                SetAttributesRepeated::Instance,
                SetParametersIndexes::Instance,
                SetTemplatesIndexes::Instance,
                ResolveClassInheritance::Instance,
                RemoveContext::Instance,
                RemoveCode::Instance,
                CleanUpInternallyDefined::Instance,
            ]),
            cache: new Cache($cache),
        );
    }

    /**
     * @return list<ConstantLocator|NamedFunctionLocator|NamedClassLocator|AnonymousLocator>
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

        $locators[] = new DontAutoloadClassLocator(new NativeReflectionClassLocator());
        $locators[] = new NativeReflectionFunctionLocator();
        $locators[] = new FileAnonymousLocator();

        return $locators;
    }

    /**
     * @param IdMap<NamedFunctionId|NamedClassId|AnonymousClassId, \Closure(self): TypedMap> $buffer
     */
    private function __construct(
        private readonly CodeReflector $codeReflector,
        private readonly Locators $locators,
        private readonly Hooks $hooks,
        private readonly Cache $cache,
        private IdMap $buffer = new IdMap(),
    ) {}

    /**
     * @param non-empty-string $name
     * @psalm-assert-if-true callable-string $name
     */
    public function functionExists(string $name): bool
    {
        try {
            $this->reflectFunction($name);

            return true;
        } catch (DeclarationNotFound) {
            return false;
        }
    }

    /**
     * @template T of object
     * @param non-empty-string $name
     * @throws DeclarationNotFound
     */
    public function reflectFunction(string $name): FunctionReflection
    {
        return $this->reflect(Id::namedFunction($name));
    }

    /**
     * @param non-empty-string $class
     * @psalm-assert-if-true class-string $class
     */
    public function classExists(string $class): bool
    {
        try {
            $this->reflectClass($class);

            return true;
        } catch (DeclarationNotFound) {
            return false;
        }
    }

    /**
     * @template TObject of object
     * @param non-empty-string|class-string<TObject> $name
     * @return ($name is class-string<TObject>
     *     ? ClassReflection<TObject, NamedClassId<class-string<TObject>>|AnonymousClassId<class-string<TObject>>>
     *     : ClassReflection<object, NamedClassId<class-string>|AnonymousClassId<?class-string>>)
     * @throws DeclarationNotFound
     */
    public function reflectClass(string $name): ClassReflection
    {
        return $this->reflect(Id::class($name));
    }

    /**
     * @param non-empty-string $file
     * @param positive-int $line
     * @param ?positive-int $column
     * @return ClassReflection<object, AnonymousClassId<null>>
     * @throws DeclarationNotFound
     */
    public function reflectAnonymousClass(string $file, int $line, ?int $column = null): ClassReflection
    {
        /** @var ClassReflection<object, AnonymousClassId<null>> */
        return $this->reflect(Id::anonymousClass($file, $line, $column));
    }

    /**
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     * @return (
     *     $id is NamedFunctionId ? FunctionReflection :
     *     $id is NamedClassId ? ClassReflection<object, NamedClassId<class-string>> :
     *     $id is AnonymousClassId<null> ? ClassReflection<object, AnonymousClassId<null>> :
     *     $id is AnonymousClassId<class-string> ? ClassReflection<object, AnonymousClassId<class-string>> :
     *     $id is ClassConstantId ? ClassConstantReflection :
     *     $id is PropertyId ? PropertyReflection :
     *     $id is MethodId ? MethodReflection :
     *     $id is ParameterId ? ParameterReflection :
     *     $id is AliasId ? AliasReflection :
     *     $id is TemplateId ? TemplateReflection :
     *     never
     * )
     * @throws DeclarationNotFound
     */
    public function reflect(Id $id): FunctionReflection|ClassReflection|ClassConstantReflection|PropertyReflection|MethodReflection|ParameterReflection|AliasReflection|TemplateReflection
    {
        try {
            if ($id instanceof NamedFunctionId) {
                return new FunctionReflection($id, $this->reflectData($id), $this);
            }

            if ($id instanceof NamedClassId || $id instanceof AnonymousClassId) {
                /** @var NamedClassId<class-string>|AnonymousClassId<?class-string> $id */
                return new ClassReflection($id, $this->reflectData($id), $this);
            }
        } finally {
            $this->buffer = $this->buffer->slice(-self::BUFFER_SIZE);
        }

        return match (true) {
            $id instanceof PropertyId => $this->reflect($id->class)->properties()[$id->name],
            $id instanceof ClassConstantId => $this->reflect($id->class)->constants()[$id->name],
            $id instanceof MethodId => $this->reflect($id->class)->methods()[$id->name],
            $id instanceof ParameterId => $this->reflect($id->function)->parameters()[$id->name],
            $id instanceof AliasId => $this->reflect($id->class)->aliases()[$id->name],
            $id instanceof TemplateId => $this->reflect($id->declaration)->templates()[$id->name],
            default => throw new \LogicException($id->describe() . ' is not supported yet'),
        };
    }

    public function withResource(Resource $resource): self
    {
        $reflectedResource = $this->reflectResource($resource);

        return new self(
            codeReflector: $this->codeReflector,
            locators: $this->locators->with(new ScannedResourceLocator($resource, $reflectedResource->ids())),
            hooks: $this->hooks,
            cache: $this->cache,
            buffer: $this->buffer->withMap($reflectedResource),
        );
    }

    private function reflectData(NamedFunctionId|NamedClassId|AnonymousClassId $id): TypedMap
    {
        $buffered = $this->buffer[$id] ?? null;

        if ($buffered !== null) {
            return $buffered($this);
        }

        $data = $this->cache->get($id);

        if ($data !== null) {
            return $data;
        }

        $resource = $this->locators->locate($id) ?? throw new DeclarationNotFound($id);
        $this->buffer = $this->buffer->withMap($this->reflectResource($resource));

        return ($this->buffer[$id] ?? throw new DeclarationNotFound($id))($this);
    }

    /**
     * @return IdMap<NamedFunctionId|NamedClassId|AnonymousClassId, \Closure(self): TypedMap>
     */
    private function reflectResource(Resource $resource): IdMap
    {
        $code = $resource->data[Data::Code];
        $file = $resource->data[Data::File];

        $idReflectors = $this->codeReflector->reflectCode($code, $file)->map(
            static fn(\Closure $idReflector, NamedFunctionId|NamedClassId|AnonymousClassId $id): \Closure =>
                static function (self $reflector) use ($resource, $id, $idReflector): TypedMap {
                    static $started = false;

                    if ($started) {
                        throw new \LogicException(sprintf('Infinite recursive reflection of %s detected', $id->describe()));
                    }

                    $started = true;

                    $data = $resource->data->withMap($idReflector());
                    $data = $resource->hooks->process($id, $data, $reflector);
                    $data = $reflector->hooks->process($id, $data, $reflector);

                    $reflector->cache->set($id, $data);
                    $reflector->buffer = $reflector->buffer->without($id);

                    return $data;
                },
        );

        return $idReflectors->withMap(self::reflectAnonymousClassesWithoutColumn($idReflectors->ids()));
    }

    /**
     * @param list<Id> $ids
     * @return IdMap<AnonymousClassId, \Closure(self): TypedMap>
     */
    private static function reflectAnonymousClassesWithoutColumn(array $ids): IdMap
    {
        return new IdMap((static function () use ($ids): \Generator {
            $lineToIds = [];

            foreach ($ids as $id) {
                if ($id instanceof AnonymousClassId) {
                    $lineToIds[$id->line][] = $id;
                }
            }

            foreach ($lineToIds as $idsOnLine) {
                $idWithoutColumn = $idsOnLine[0]->withoutColumn();

                if (\count($idsOnLine) === 1) {
                    yield $idWithoutColumn => static fn(self $reflector): TypedMap => $reflector->reflectData($idsOnLine[0]);

                    continue;
                }

                yield $idWithoutColumn => static function () use ($idWithoutColumn, $idsOnLine): never {
                    throw new \RuntimeException(sprintf(
                        'Cannot reflect %s, because %d anonymous classes are declared at columns %s. ' .
                        'Use TyphoonReflector::reflectAnonymousClass() with a $column argument to reflect the exact class you need',
                        $idWithoutColumn->describe(),
                        \count($idsOnLine),
                        implode(', ', array_column($idsOnLine, 'column')),
                    ));
                };
            }
        })());
    }
}
