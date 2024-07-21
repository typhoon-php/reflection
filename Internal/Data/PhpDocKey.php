<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use PhpParser\Comment\Doc;
use Typhoon\Reflection\Internal\TypedMap\OptionalKey;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements OptionalKey<?Doc>
 */
enum PhpDocKey implements OptionalKey
{
    case Key;

    public function default(TypedMap $map): mixed
    {
        return null;
    }
}
