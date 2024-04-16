<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

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

    public function evaluate(EvaluationContext $context): mixed
    {
        /** @psalm-suppress MixedArrayAccess, MixedArrayOffset */
        return $this->array->evaluate($context)[$this->key->evaluate($context)];
    }
}
