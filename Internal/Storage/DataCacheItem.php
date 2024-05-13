<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Storage;

use Typhoon\Reflection\Internal\Data;
use Typhoon\TypedMap\TypedMap;

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
        return ($this->get()[Data::ResolvedChangeDetector] ?? null)?->changed() ?? true;
    }

    public function __serialize(): array
    {
        return ['data' => $this->get()];
    }
}
