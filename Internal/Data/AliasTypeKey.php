<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Reflection\Internal\TypedMap\Key;
use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements Key<Type>
 */
enum AliasTypeKey implements Key
{
    case Key;
}
