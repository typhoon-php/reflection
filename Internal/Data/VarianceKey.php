<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Reflection\Internal\TypedMap\OptionalKey;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Type\Variance;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements OptionalKey<Variance>
 */
enum VarianceKey implements OptionalKey
{
    case Key;

    public function default(TypedMap $map): mixed
    {
        return Variance::Invariant;
    }
}
