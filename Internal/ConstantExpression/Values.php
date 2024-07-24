<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<null|bool|-1|0|1|''|array{}>
 */
enum Values implements Expression
{
    case Null;
    case True;
    case False;
    case MinusOne;
    case Zero;
    case One;
    case EmptyString;
    case EmptyArray;

    public function recompile(string $self, ?string $parent): Expression
    {
        return $this;
    }

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        return match ($this) {
            self::Null => null,
            self::True => true,
            self::False => false,
            self::MinusOne => -1,
            self::Zero => 0,
            self::One => 1,
            self::EmptyString => '',
            self::EmptyArray => [],
        };
    }
}
