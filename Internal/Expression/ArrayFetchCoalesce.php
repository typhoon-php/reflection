<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\Reflector;

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

    public function evaluate(Reflector $reflector): mixed
    {
        /** @psalm-suppress MixedArrayOffset */
        return $this->array->evaluate($reflector)[$this->key->evaluate($reflector)] ?? $this->default->evaluate($reflector);
    }
}
