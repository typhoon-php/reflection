<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon
 * @psalm-immutable
 * @template TValue
 * @extends Key<TValue>
 */
interface OptionalKey extends Key
{
    /**
     * @return TValue
     */
    public function default(TypedMap $map): mixed;
}
