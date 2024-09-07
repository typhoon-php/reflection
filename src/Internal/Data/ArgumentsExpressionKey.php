<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Reflection\Internal\ConstantExpression\Expression;
use Typhoon\Reflection\Internal\ConstantExpression\Value;
use Typhoon\TypedMap\OptionalKey;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements OptionalKey<Expression<array>>
 */
enum ArgumentsExpressionKey implements OptionalKey
{
    case Key;

    public function default(TypedMap $map): mixed
    {
        return Value::from([]);
    }
}
