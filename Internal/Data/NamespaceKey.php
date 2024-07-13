<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Reflection\Internal\TypedMap\Key;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements Key<string>
 */
enum NamespaceKey implements Key
{
    case Key;
}
