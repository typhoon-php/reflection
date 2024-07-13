<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<?bool>
 */
enum Values implements Expression
{
    case Null;
    case True;
    case False;

    public function evaluate(?TyphoonReflector $reflector = null): ?bool
    {
        return match ($this) {
            self::Null => null,
            self::True => true,
            self::False => false,
        };
    }
}
