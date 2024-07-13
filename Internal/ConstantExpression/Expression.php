<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template-covariant T
 */
interface Expression
{
    /**
     * @return T
     */
    public function evaluate(?TyphoonReflector $reflector = null): mixed;
}
