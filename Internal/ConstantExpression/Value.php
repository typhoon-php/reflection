<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\Reflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class Value implements Expression
{
    public function __construct(
        private readonly int|float|string|array $value,
    ) {}

    public function evaluate(?Reflector $reflector = null): mixed
    {
        return $this->value;
    }
}
