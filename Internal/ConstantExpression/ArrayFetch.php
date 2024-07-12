<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\Reflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ArrayFetch implements Expression
{
    public function __construct(
        private readonly Expression $array,
        private readonly Expression $key,
    ) {}

    public function evaluate(?Reflector $reflector = null): mixed
    {
        /** @psalm-suppress MixedArrayAccess, MixedArrayOffset */
        return $this->array->evaluate($reflector)[$this->key->evaluate($reflector)];
    }
}
