<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Reflection\Internal\TypedMap\OptionalKey;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @psalm-type MethodName = non-empty-string
 * @psalm-type TraitName = non-empty-string
 * @implements OptionalKey<array<MethodName, TraitName>>
 */
enum TraitMethodPrecedence implements OptionalKey
{
    case Key;

    public function default(TypedMap $map): mixed
    {
        return [];
    }
}
