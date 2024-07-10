<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\Reflector;

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

    public function evaluate(?Reflector $reflector = null): mixed
    {
        if ($this->if === null) {
            return $this->condition->evaluate($reflector) ?: $this->else->evaluate($reflector);
        }

        return $this->condition->evaluate($reflector) ? $this->if->evaluate($reflector) : $this->else->evaluate($reflector);
    }
}
