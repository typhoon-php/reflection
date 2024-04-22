<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\Internal\ClassReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface Expression
{
    public function evaluate(ClassReflector $classReflector): mixed;
}
