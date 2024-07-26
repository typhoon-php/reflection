<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Type\Variance;
use Typhoon\TypedMap\OptionalKey;
use Typhoon\TypedMap\TypedMap;

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
