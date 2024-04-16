<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ClassConstantFetch implements Expression
{
    public function __construct(
        private readonly Expression $class,
        private readonly Expression $name,
    ) {}

    public function evaluate(EvaluationContext $context): mixed
    {
        /** @psalm-suppress MixedArgument */
        return $context->classConstant(
            class: $this->class->evaluate($context),
            name: $this->name->evaluate($context),
        );
    }
}
