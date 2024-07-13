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
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\DeclarationId\PropertyId;
use Typhoon\DeclarationId\TemplateId;
use Typhoon\PhpStormReflectionStubs\PhpStormStubsLocator;
use Typhoon\Reflection\Cache\InMemoryCache;
use Typhoon\Reflection\Exception\ClassDoesNotExist;
use Typhoon\Reflection\Internal\Cache\Cache;
use Typhoon\Reflection\Internal\Cache\DataCacheItem;
use Typhoon\Reflection\Internal\CodeReflector\CodeReflector;
use Typhoon\Reflection\Internal\CompleteReflection\AddStringableInterface;
use Typhoon\Reflection\Internal\CompleteReflection\CleanUp;
use Typhoon\Reflection\Internal\CompleteReflection\CompleteEnumReflection;
use Typhoon\Reflection\Internal\CompleteReflection\CopyPromotedParametersToProperties;
use Typhoon\Reflection\Internal\CompleteReflection\EnsureInterfaceMethodsAreAbstract;
use Typhoon\Reflection\Internal\CompleteReflection\EnsureReadonlyClassPropertiesAreReadonly;
use Typhoon\Reflection\Internal\CompleteReflection\ResolveAttributesRepeated;
use Typhoon\Reflection\Internal\CompleteReflection\ResolveChangeDetector;
use Typhoon\Reflection\Internal\CompleteReflection\ResolveParametersIndex;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\DataReflector;
use Typhoon\Reflection\Internal\DeclarationId\IdMap;
use Typhoon\Reflection\Internal\Inheritance\ResolveClassInheritance;
use Typhoon\Reflection\Internal\PhpDoc\ReflectPhpDocTypes;
use Typhoon\Reflection\Internal\ReflectionHook\ReflectionHooks;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Reflection\Locator\AnonymousLocator;
use Typhoon\Reflection\Locator\ComposerLocator;
use Typhoon\Reflection\Locator\ConstantLocator;
use Typhoon\Reflection\Locator\DeterministicLocator;
use Typhoon\Reflection\Locator\FileAnonymousLocator;
use Typhoon\Reflection\Locator\Locators;
use Typhoon\Reflection\Locator\NamedClassLocator;
use Typhoon\Reflection\Locator\NamedFunctionLocator;
use Typhoon\Reflection\Locator\NativeReflectionClassLocator;
use Typhoon\Reflection\Locator\NativeReflectionFunctionLocator;

/**
 * @api
 */
final class TyphoonReflector extends Reflector implements DataReflector
{
    /**
     * @param ?list<ConstantLocator|NamedFunctionLocator|NamedClassLocator|AnonymousLocator> $locators
     */
    public static function build(
        ?array $locators = null,
        CacheInterface $cache = new InMemoryCache(),
        ?Parser $phpParser = null,
    ): self {
        $reflectPhpDocTypes = new ReflectPhpDocTypes();

        return new self(
            codeReflector: new CodeReflector(
                phpParser: $phpParser ?? (new ParserFactory())->createForHostVersion(),
                annotatedTypesDriver: $reflectPhpDocTypes,
            ),
            locators: new Locators($locators ?? self::defaultLocators()),
            cache: new Cache($cache),
            hooks: new ReflectionHooks([
                $reflectPhpDocTypes,
                new CopyPromotedParametersToProperties(),
                new CompleteEnumReflection(),
                new AddStringableInterface(),
                new ResolveClassInheritance(),
                new EnsureInterfaceMethodsAreAbstract(),
                new EnsureReadonlyClassPropertiesAreReadonly(),
                new ResolveAttributesRepeated(),
                new ResolveParametersIndex(),
                new ResolveChangeDetector(),
                new CleanUp(),
            ]),
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

        $locators[] = new NativeReflectionClassLocator();
        $locators[] = new NativeReflectionFunctionLocator();
        $locators[] = new FileAnonymousLocator();

        return $locators;
    }

    /**
     * @var IdMap<NamedClassId|AnonymousClassId, DataCacheItem>
     */
    private IdMap $reflected;

    private function __construct(
        private readonly CodeReflector $codeReflector,
        private readonly Locators $locators,
        private readonly Cache $cache,
        private readonly ReflectionHooks $hooks,
    ) {
        /** @var IdMap<NamedClassId|AnonymousClassId, DataCacheItem> */
        $this->reflected = new IdMap();
    }

    /**
     * @return (
     *     $id is NamedClassId ? ClassReflection :
     *     $id is AnonymousClassId ? ClassReflection :
     *     $id is ClassConstantId ? ClassConstantReflection :
     *     $id is PropertyId ? PropertyReflection :
     *     $id is MethodId ? MethodReflection :
     *     $id is ParameterId ? ParameterReflection :
     *     $id is AliasId ? AliasReflection :
     *     $id is TemplateId ? TemplateReflection :
     *     never
     * )
     */
    public function reflect(Id $id): ClassReflection|ClassConstantReflection|PropertyReflection|MethodReflection|ParameterReflection|AliasReflection|TemplateReflection
    {
        if ($id instanceof NamedClassId || $id instanceof AnonymousClassId) {
            $data = $this->reflectData($id);
            $this->flush();

            return new ClassReflection($id, $data, $this);
        }

        return match (true) {
            $id instanceof PropertyId => $this->reflect($id->class)->properties()[$id->name],
            $id instanceof ClassConstantId => $this->reflect($id->class)->constants()[$id->name],
            $id instanceof MethodId => $this->reflect($id->class)->methods()[$id->name],
            $id instanceof ParameterId => $this->reflect($id->function)->parameters()[$id->name],
            $id instanceof AliasId => $this->reflect($id->class)->aliases()[$id->name],
            $id instanceof TemplateId => $this->reflect($id->site)->templates()[$id->name],
            default => throw new \LogicException($id->toString() . ' is not supported yet'),
        };
    }

    public function reflectResource(Resource $resource): static
    {
        $reflected = $this->codeReflector->reflectCode($resource->code, $resource->baseData[Data::File]);
        $this->reflected = $this->reflected->withMultiple((function () use ($reflected, $resource): \Generator {
            foreach ($reflected as $id => $data) {
                yield $id => $this->buildCacheItem($resource, $id, $data);
            }
        })());

        return new self(
            codeReflector: $this->codeReflector,
            locators: new Locators([
                new DeterministicLocator($reflected->map(static fn(): Resource => $resource)),
                $this->locators,
            ]),
            cache: $this->cache,
            hooks: $this->hooks,
        );
    }

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public function reflectData(NamedClassId|AnonymousClassId $id): TypedMap
    {
        $cacheItem = $this->reflected[$id] ?? $this->cache->get($id);

        if ($cacheItem instanceof DataCacheItem) {
            return $cacheItem->get();
        }

        $resource = $this->locators->locate($id);

        if ($resource === null) {
            throw new ClassDoesNotExist($id->name ?? $id->toString());
        }

        $this->reflected = $this->reflected->withMultiple((function () use ($resource): \Generator {
            foreach ($this->codeReflector->reflectCode($resource->code, $resource->baseData[Data::File]) as $id => $data) {
                yield $id => $this->buildCacheItem($resource, $id, $data);
            }
        })());

        $cacheItem = $this->reflected[$id] ?? throw new ClassDoesNotExist($id->name ?? $id->toString());

        return $cacheItem->get();
    }

    private function buildCacheItem(Resource $resource, NamedClassId|AnonymousClassId $id, TypedMap $data): DataCacheItem
    {
        return new DataCacheItem(function () use ($resource, $id, $data): TypedMap {
            $data = $resource->baseData->merge($data);
            $data = (new ReflectionHooks($resource->hooks))->process($id, $data, $this);

            return $this->hooks->process($id, $data, $this);
        });
    }

    private function flush(): void
    {
        $this->cache->set($this->reflected);
        /** @var IdMap<NamedClassId|AnonymousClassId, DataCacheItem> */
        $this->reflected = new IdMap();
    }
}
