<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\TypedMap\OptionalKey;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements OptionalKey<NativeTraitInfo>
 */
enum NativeTraitInfoKey implements OptionalKey
{
    case Key;

    public function default(TypedMap $map): mixed
    {
        return new NativeTraitInfo();
    }
}
