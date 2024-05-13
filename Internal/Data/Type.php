<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Reflection\Internal\TypeData;
use Typhoon\TypedMap\OptionalKey;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements OptionalKey<TypeData>
 */
enum Type implements OptionalKey
{
    case Key;

    public function default(TypedMap $map): mixed
    {
        return new TypeData();
    }
}
