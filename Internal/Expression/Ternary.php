<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\Reflection;
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

    public function evaluate(Reflection $reflection, Reflector $reflector): mixed
    {
        if ($this->if === null) {
            return $this->condition->evaluate($reflection, $reflector) ?: $this->else->evaluate($reflection, $reflector);
        }

        return $this->condition->evaluate($reflection, $reflector) ? $this->if->evaluate($reflection, $reflector) : $this->else->evaluate($reflection, $reflector);
    }
}
