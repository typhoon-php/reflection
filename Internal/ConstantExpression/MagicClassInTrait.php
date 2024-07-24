<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<string>
 */
final class MagicClassInTrait implements Expression
{
    public function __construct(
        private readonly string $trait,
    ) {}

    public function recompile(CompilationContext $context): Expression
    {
        $expression = $context->magicClass();

        if ($expression instanceof self) {
            return $expression;
        }

        // even when trait is used in a class, __CONSTANT__ is not inlined
        return new self($expression->evaluate());
    }

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        return $this->trait;
    }
}
