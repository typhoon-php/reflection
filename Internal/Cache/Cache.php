<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Cache;

use Psr\SimpleCache\CacheInterface;
use Typhoon\DeclarationId\Id;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class Cache
{
    /**
     * This version should be incremented before release if any of the cacheable elements have changed.
     * This should help to avoid deserialization errors.
     */
    private const VERSION = 0;
    private const PREFIX = 'typhoon/reflection';

    public function __construct(
        private readonly CacheInterface $cache,
    ) {}

    public function get(Id $id): ?TypedMap
    {
        $value = $this->cache->get(self::key($id));

        if ($value instanceof TypedMap) {
            return $value;
        }

        return null;
    }

    /**
     * @param iterable<Id, TypedMap> $data
     */
    public function set(iterable $data): void
    {
        if ($data === []) {
            return;
        }

        $this->cache->setMultiple((static function () use ($data): \Generator {
            foreach ($data as $id => $item) {
                yield self::key($id) => $item;
            }
        })());
    }

    private static function key(Id $id): string
    {
        return hash('xxh128', self::PREFIX . $id->encode() . self::VERSION);
    }
}
