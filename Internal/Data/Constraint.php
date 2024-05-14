<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Type\Type as TypeAlias;
use Typhoon\Type\types;
use Typhoon\TypedMap\OptionalKey;
use Typhoon\TypedMap\TypedMap;

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
