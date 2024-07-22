<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<mixed>
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

    public function recompile(string $self, ?string $parent): Expression
    {
        return new self(
            expression: $this->expression->recompile($self, $parent),
            operator: $this->operator,
        );
    }

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        return match ($this->operator) {
            '+' => +$this->expression->evaluate($reflector),
            '-' => -$this->expression->evaluate($reflector),
            '!' => !$this->expression->evaluate($reflector),
            '~' => ~$this->expression->evaluate($reflector),
        };
    }
}
