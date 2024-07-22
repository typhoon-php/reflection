<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<non-empty-string>
 */
final class TraitSelf implements Expression
{
    /**
     * @param non-empty-string $trait
     */
    public function __construct(
        private readonly string $trait,
    ) {}

    public function recompile(string $self, ?string $parent): Expression
    {
        return new Value($self);
    }

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        return $this->trait;
    }
}
