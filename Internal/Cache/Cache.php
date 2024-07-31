<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Cache;

use Psr\SimpleCache\CacheInterface;
use Typhoon\DeclarationId\Id;
use Typhoon\TypedMap\TypedMap;

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

    public function set(Id $id, TypedMap $data): void
    {
        $this->cache->set(self::key($id), $data);
    }

    private static function key(Id $id): string
    {
        return hash('xxh128', self::PREFIX . $id->encode() . self::VERSION);
    }
}
