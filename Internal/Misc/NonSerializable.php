<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Misc;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
trait NonSerializable
{
    final public function __serialize(): never
    {
        throw new \LogicException(\sprintf('Object of class %s must not be serialized', self::class));
    }
}
