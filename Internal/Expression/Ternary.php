<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class Ternary implements Expression
{
    public function __construct(
        private readonly Expression $condition,
        private readonly ?Expression $if,
        private readonly Expression $else,
    ) {}

    public function evaluate(EvaluationContext $context): mixed
    {
        if ($this->if === null) {
            return $this->condition->evaluate($context) ?: $this->else->evaluate($context);
        }

        return $this->condition->evaluate($context) ? $this->if->evaluate($context) : $this->else->evaluate($context);
    }
}
