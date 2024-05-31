<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\DeclarationId\ClassId;
use Typhoon\TypedMap\Key;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements Key<ClassId>
 */
enum DeclaringClassId implements Key
{
    case Key;
}
