<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\TypedMap\OptionalKey;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements OptionalKey<bool>
 */
enum IsPromoted implements OptionalKey
{
    case Key;

    public function default(TypedMap $map): mixed
    {
        return false;
    }
}
