<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Reflection\Internal\ClassKind;
use Typhoon\Reflection\Internal\TypedMap\Key;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements Key<ClassKind>
 */
enum ClassKindKey implements Key
{
    case Key;
}
