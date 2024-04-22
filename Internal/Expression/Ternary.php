<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\Internal\ClassReflector;

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

    public function evaluate(ClassReflector $classReflector): mixed
    {
        if ($this->if === null) {
            return $this->condition->evaluate($classReflector) ?: $this->else->evaluate($classReflector);
        }

        return $this->condition->evaluate($classReflector) ? $this->if->evaluate($classReflector) : $this->else->evaluate($classReflector);
    }
}
