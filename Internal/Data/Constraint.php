<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Reflection\Internal\TypedMap\OptionalKey;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Type\Type as TypeAlias;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements OptionalKey<TypeAlias>
 */
enum Constraint implements OptionalKey
{
    case Key;

    public function default(TypedMap $map): mixed
    {
        return types::mixed;
    }
}
