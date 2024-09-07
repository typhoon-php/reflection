<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum PassedBy
{
    case Value;
    case Reference;
    case ValueOrReference;
}
