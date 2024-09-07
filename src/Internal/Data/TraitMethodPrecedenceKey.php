<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\TypedMap\OptionalKey;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @psalm-type MethodName = non-empty-string
 * @psalm-type TraitName = non-empty-string
 * @implements OptionalKey<array<MethodName, TraitName>>
 */
enum TraitMethodPrecedenceKey implements OptionalKey
{
    case Key;

    public function default(TypedMap $map): mixed
    {
        return [];
    }
}
