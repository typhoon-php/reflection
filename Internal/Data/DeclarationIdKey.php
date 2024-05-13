<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\DeclarationId\DeclarationId;
use Typhoon\TypedMap\Key;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements Key<DeclarationId>
 */
enum DeclarationIdKey implements Key
{
    case Key;
}
