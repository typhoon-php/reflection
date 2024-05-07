<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\Reflection;
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

    public function evaluate(Reflection $reflection, Reflector $reflector): mixed
    {
        /** @psalm-suppress MixedArrayOffset */
        return $this->array->evaluate($reflection, $reflector)[$this->key->evaluate($reflection, $reflector)] ?? $this->default->evaluate($reflection, $reflector);
    }
}
