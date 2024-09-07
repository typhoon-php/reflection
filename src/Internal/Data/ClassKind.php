<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum ClassKind
{
    case Class_;
    case Interface;
    case Enum;
    case Trait;
}
