<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Exception\ClassDoesNotExist;
use Typhoon\Reflection\Internal\Cache\Cache;
use Typhoon\Reflection\Internal\Cache\DataCacheItem;
use Typhoon\Reflection\Internal\CodeReflector\CodeReflector;
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
     * @var IdMap<NamedFunctionId|NamedClassId|AnonymousClassId, DataCacheItem>
     */
    private IdMap $buffer;

    private function __construct(
        private readonly CodeReflector $codeReflector,
        private readonly Locators $locators,
        private readonly Cache $cache,
        private readonly ReflectionHooks $hooks,
    ) {
        /** @var IdMap<NamedFunctionId|NamedClassId|AnonymousClassId, DataCacheItem> */
        $this->buffer = new IdMap();
    }

    public static function reflectId(
        CodeReflector $codeReflector,
        Locators $locators,
        Cache $cache,
        ReflectionHooks $hooks,
        NamedFunctionId|NamedClassId|AnonymousClassId $id,
    ): TypedMap {
        $session = new self(
            codeReflector: $codeReflector,
            locators: $locators,
            cache: $cache,
            hooks: $hooks,
        );
        $data = $session->reflect($id);
        $cache->set($session->buffer);

        return $data;
    }

    /**
     * @return list<NamedFunctionId|NamedClassId|AnonymousClassId>
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
        $session->reflectResourceIntoBuffer($resource);
        $cache->set($session->buffer);

        return $session->buffer->ids();
    }

    public function reflect(NamedFunctionId|NamedClassId|AnonymousClassId $id): TypedMap
    {
        $cacheItem = $this->buffer[$id] ?? $this->cache->get($id);

        if ($cacheItem !== null) {
            return $cacheItem->get();
        }

        $resource = $this->locators->locate($id);

        if ($resource === null) {
            throw new ClassDoesNotExist($id->name ?? $id->toString());
        }

        $this->reflectResourceIntoBuffer($resource);

        $cacheItem = $this->buffer[$id] ?? throw new ClassDoesNotExist($id->name ?? $id->toString());

        return $cacheItem->get();
    }

    private function reflectResourceIntoBuffer(Resource $resource): void
    {
        $reflected = $this->codeReflector
            ->reflectCode($resource->code, $resource->baseData)
            ->map(fn(TypedMap $data, NamedFunctionId|NamedClassId|AnonymousClassId $id): DataCacheItem => new DataCacheItem(
                function () use ($resource, $id, $data): TypedMap {
                    $data = $resource->hooks->process($id, $data, $this);

                    return $this->hooks->process($id, $resource->hooks->process($id, $data, $this), $this);
                },
            ));
        $this->buffer = $this->buffer->withMultiple($reflected);
    }
}
