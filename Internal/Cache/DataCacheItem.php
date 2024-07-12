<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Cache;

use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class DataCacheItem
{
    /**
     * @param TypedMap|\Closure(): TypedMap $data
     */
    public function __construct(
        private TypedMap|\Closure $data,
    ) {}

    public function get(): TypedMap
    {
        if ($this->data instanceof \Closure) {
            return $this->data = ($this->data)();
        }

        return $this->data;
    }

    public function changed(): bool
    {
        return ($this->get()[Data::ChangeDetector] ?? null)?->changed() ?? true;
    }

    public function __serialize(): array
    {
        return ['data' => $this->get()];
    }
}
