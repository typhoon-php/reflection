<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\Reflector;

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

    public function evaluate(?Reflector $reflector = null): ?bool
    {
        return match ($this) {
            self::Null => null,
            self::True => true,
            self::False => false,
        };
    }
}
