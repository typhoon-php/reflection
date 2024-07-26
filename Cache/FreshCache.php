<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Cache;

use Psr\SimpleCache\CacheInterface;
use Typhoon\Reflection\Internal\Data;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 */
final class FreshCache implements CacheInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {}

    private static function isStale(mixed $value): bool
    {
        if (!$value instanceof TypedMap) {
            return false;
        }

        $changeDetector = $value[Data::ChangeDetector] ?? null;

        return $changeDetector !== null && $changeDetector->changed();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->cache->get($key, $default);

        return self::isStale($value) ? $default : $value;
    }

    public function set(string $key, mixed $value, null|\DateInterval|int $ttl = null): bool
    {
        return $this->cache->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }

    public function clear(): bool
    {
        return $this->cache->clear();
    }

    /**
     * @param iterable<string> $keys
     * @return \Generator<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($this->cache->getMultiple($keys) as $key => $value) {
            yield $key => self::isStale($value) ? $default : $value;
        }
    }

    public function setMultiple(iterable $values, null|\DateInterval|int $ttl = null): bool
    {
        return $this->cache->setMultiple($values, $ttl);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return $this->cache->deleteMultiple($keys);
    }

    public function has(string $key): bool
    {
        return $this->cache->get($key, $this) !== $this;
    }
}
