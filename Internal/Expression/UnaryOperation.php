<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class UnaryOperation implements Expression
{
    /**
     * @param non-empty-string $operator
     */
    public function __construct(
        private readonly Expression $expression,
        private readonly string $operator,
    ) {}

    public function evaluate(EvaluationContext $context): mixed
    {
        return match ($this->operator) {
            '+' => +$this->expression->evaluate($context),
            '-' => -$this->expression->evaluate($context),
            '!' => !$this->expression->evaluate($context),
            '~' => ~$this->expression->evaluate($context),
        };
    }
}
