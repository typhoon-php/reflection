<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\TypedMap\OptionalKey;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements OptionalKey<TypeData>
 */
enum TypeDataKeys implements OptionalKey
{
    case Type;

    public function default(TypedMap $map): mixed
    {
        return new TypeData();
    }
}
