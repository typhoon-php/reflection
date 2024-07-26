<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\TypedMap\OptionalKey;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements OptionalKey<Type>
 */
enum ConstraintKey implements OptionalKey
{
    case Key;

    public function default(TypedMap $map): mixed
    {
        return types::mixed;
    }
}
