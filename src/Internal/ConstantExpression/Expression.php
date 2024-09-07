<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon
 * @template-covariant T
 */
interface Expression
{
    /**
     * @return Expression<T>
     */
    public function recompile(CompilationContext $context): self;

    /**
     * @return T
     */
    public function evaluate(?TyphoonReflector $reflector = null): mixed;
}
