<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<non-empty-string>
 */
enum ParentClassInTrait implements Expression
{
    case Instance;

    public function recompile(CompilationContext $context): Expression
    {
        return $context->parent();
    }

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        throw new \LogicException('Parent in trait!');
    }
}
