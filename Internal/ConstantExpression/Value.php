<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template-covariant TValue
 * @implements Expression<TValue>
 */
final class Value implements Expression
{
    /**
     * @param TValue $value
     */
    private function __construct(
        public readonly mixed $value,
    ) {}

    /**
     * @template T
     * @param T $value
     * @return Expression<T>
     */
    public static function from(mixed $value): Expression
    {
        /** @var Expression<T> */
        return match ($value) {
            null => Values::Null,
            true => Values::True,
            false => Values::False,
            -1 => Values::MinusOne,
            0 => Values::Zero,
            1 => Values::One,
            '' => Values::EmptyString,
            [] => Values::EmptyArray,
            default => new self($value),
        };
    }

    public function recompile(string $self, ?string $parent): Expression
    {
        return $this;
    }

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        return $this->value;
    }
}
