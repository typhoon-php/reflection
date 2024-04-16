<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

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

    public function evaluate(EvaluationContext $context): mixed
    {
        /** @psalm-suppress MixedArrayOffset */
        return $this->array->evaluate($context)[$this->key->evaluate($context)] ?? $this->default->evaluate($context);
    }
}
