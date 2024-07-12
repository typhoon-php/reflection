<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\Reflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum Values implements Expression
{
    case null;
    case true;
    case false;
    case MinusOne;
    case Zero;
    case One;
    case EmptyString;

    public function evaluate(?Reflector $reflector = null): mixed
    {
        return match ($this) {
            self::null => null,
            self::true => true,
            self::false => false,
            self::MinusOne => -1,
            self::Zero => 0,
            self::One => 1,
            self::EmptyString => '',
        };
    }
}
