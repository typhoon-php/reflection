<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Reflection\Internal\TypedMap\OptionalKey;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Reflection\Location;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements OptionalKey<?Location>
 */
enum LocationKey implements OptionalKey
{
    case Key;

    public function default(TypedMap $map): mixed
    {
        return null;
    }
}
