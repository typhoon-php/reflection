<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\ClassConstantReflection;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\MethodReflection;
use Typhoon\Reflection\PropertyReflection;
use Typhoon\Reflection\Reflection;
use Typhoon\Reflection\Reflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum MagicNamespace implements Expression
{
    case Constant;

    public function evaluate(Reflection $reflection, Reflector $reflector): string
    {
        return match (true) {
            $reflection instanceof ClassReflection => $reflection->namespace(),
            $reflection instanceof ClassConstantReflection => $reflection->class()->namespace(),
            $reflection instanceof PropertyReflection => $reflection->class()->namespace(),
            $reflection instanceof MethodReflection => $reflection->class()->namespace(),
            default => '',
        };
    }
}
