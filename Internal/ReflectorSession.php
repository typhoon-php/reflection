<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Exception\ClassDoesNotExist;
use Typhoon\Reflection\Internal\Cache\Cache;
use Typhoon\Reflection\Internal\Cache\DataCacheItem;
use Typhoon\Reflection\Internal\CodeReflector\CodeReflector;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\DeclarationId\IdMap;
use Typhoon\Reflection\Internal\ReflectionHook\ReflectionHooks;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Reflection\Locator\Locators;
use Typhoon\Reflection\Resource;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ReflectorSession implements Reflector
{
    /**
     * @var IdMap<NamedClassId|AnonymousClassId, DataCacheItem>
     */
    private IdMap $buffer;

    private function __construct(
        private readonly CodeReflector $codeReflector,
        private readonly Locators $locators,
        private readonly Cache $cache,
        private readonly ReflectionHooks $hooks,
    ) {
        /** @var IdMap<NamedClassId|AnonymousClassId, DataCacheItem> */
        $this->buffer = new IdMap();
    }

    public static function reflectId(
        CodeReflector $codeReflector,
        Locators $locators,
        Cache $cache,
        ReflectionHooks $hooks,
        NamedClassId|AnonymousClassId $id,
    ): TypedMap {
        $session = new self(
            codeReflector: $codeReflector,
            locators: $locators,
            cache: $cache,
            hooks: $hooks,
        );
        $data = $session->reflect($id);
        $session->persist();

        return $data;
    }

    /**
     * @return list<NamedClassId|AnonymousClassId>
     */
    public static function reflectResource(
        CodeReflector $codeReflector,
        Locators $locators,
        Cache $cache,
        ReflectionHooks $hooks,
        Resource $resource,
    ): array {
        $session = new self(
            codeReflector: $codeReflector,
            locators: $locators,
            cache: $cache,
            hooks: $hooks,
        );
        $ids = $session->doReflectResource($resource);
        $session->persist();

        return $ids;
    }

    public function reflect(NamedClassId|AnonymousClassId $id): TypedMap
    {
        $cacheItem = $this->buffer[$id] ?? $this->cache->get($id);

        if ($cacheItem !== null) {
            return $cacheItem->get();
        }

        $resource = $this->locators->locate($id);

        if ($resource === null) {
            throw new ClassDoesNotExist($id->name ?? $id->toString());
        }

        $this->doReflectResource($resource);

        $cacheItem = $this->buffer[$id] ?? throw new ClassDoesNotExist($id->name ?? $id->toString());

        return $cacheItem->get();
    }

    /**
     * @return list<NamedClassId|AnonymousClassId>
     */
    private function doReflectResource(Resource $resource): array
    {
        $reflected = $this->codeReflector
            ->reflectCode($resource->code, $resource->baseData[Data::File])
            ->map(fn(TypedMap $data, NamedClassId|AnonymousClassId $id): DataCacheItem => new DataCacheItem(
                function () use ($resource, $id, $data): TypedMap {
                    $data = $resource->baseData->merge($data);
                    $data = (new ReflectionHooks($resource->hooks))->process($id, $data, $this);

                    return $this->hooks->process($id, $data, $this);
                },
            ));
        $this->buffer = $this->buffer->withMultiple($reflected);

        return $reflected->ids();
    }

    private function persist(): void
    {
        $this->cache->set($this->buffer);
    }
}
