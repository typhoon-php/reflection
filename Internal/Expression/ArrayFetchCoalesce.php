<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\Internal\ClassReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ArrayFetchCoalesce implements Expression
{
    public function __construct(
        private readonly Expression $array,
        private readonly Expression $key,
        private readonly Expression $default,
    ) {}

    public function evaluate(ClassReflector $classReflector): mixed
    {
        /** @psalm-suppress MixedArrayOffset */
        return $this->array->evaluate($classReflector)[$this->key->evaluate($classReflector)] ?? $this->default->evaluate($classReflector);
    }
}
