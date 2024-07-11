<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Psr\SimpleCache\CacheInterface;
use Typhoon\DeclarationId\Id;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class Cache
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {}

    public function get(Id $id): ?DataCacheItem
    {
        $value = $this->cache->get(self::key($id));

        if ($value instanceof DataCacheItem) {
            return $value;
        }

        return null;
    }

    /**
     * @param IdMap<Id, DataCacheItem> $data
     */
    public function setFrom(IdMap $data): void
    {
        $values = [];

        foreach ($data as $id => $item) {
            $values[self::key($id)] = $item;
        }

        if ($values !== []) {
            $this->cache->setMultiple($values);
        }
    }

    private static function key(Id $id): string
    {
        return hash('xxh128', $id->encode());
    }
}
