<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<non-empty-string>
 */
enum TraitParent implements Expression
{
    case Instance;

    public function recompile(string $self, ?string $parent): Expression
    {
        return new Value($self);
    }

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        throw new \LogicException('Parent in trait');
    }
}
