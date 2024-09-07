<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<mixed>
 */
final class Ternary implements Expression
{
    public function __construct(
        private readonly Expression $condition,
        private readonly ?Expression $if,
        private readonly Expression $else,
    ) {}

    public function recompile(CompilationContext $context): Expression
    {
        return new self(
            condition: $this->condition->recompile($context),
            if: $this->if?->recompile($context),
            else: $this->else,
        );
    }

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        if ($this->if === null) {
            return $this->condition->evaluate($reflector) ?: $this->else->evaluate($reflector);
        }

        return $this->condition->evaluate($reflector) ? $this->if->evaluate($reflector) : $this->else->evaluate($reflector);
    }
}
