<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<object>
 */
final class Instantiation implements Expression
{
    /**
     * @param array<Expression> $arguments
     */
    public function __construct(
        private readonly Expression $class,
        private readonly array $arguments,
    ) {}

    public function recompile(CompilationContext $context): Expression
    {
        return new self(
            class: $this->class->recompile($context),
            arguments: array_map(
                static fn(Expression $expression): Expression => $expression->recompile($context),
                $this->arguments,
            ),
        );
    }

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        /** @psalm-suppress MixedMethodCall */
        return new ($this->class->evaluate($reflector))(...array_map(
            static fn(Expression $expression): mixed => $expression->evaluate($reflector),
            $this->arguments,
        ));
    }
}
