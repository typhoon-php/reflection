<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template-covariant TValue of int|float|string|array
 * @implements Expression<TValue>
 */
final class Value implements Expression
{
    /**
     * @param TValue $value
     */
    public function __construct(
        public readonly int|float|string|array $value,
    ) {}

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        return $this->value;
    }
}
