<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\ClassConstantReflection;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\MethodReflection;
use Typhoon\Reflection\ParameterReflection;
use Typhoon\Reflection\PropertyReflection;
use Typhoon\Reflection\Reflection;
use Typhoon\Reflection\Reflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum MagicClass implements Expression
{
    case Constant;

    public function evaluate(Reflection $reflection, Reflector $reflector): string
    {
        return match (true) {
            $reflection instanceof ClassReflection => $reflection->name,
            $reflection instanceof ClassConstantReflection => $reflection->class()->name,
            $reflection instanceof PropertyReflection => $reflection->class()->name,
            $reflection instanceof MethodReflection => $reflection->class()->name,
            $reflection instanceof ParameterReflection => $reflection->class()?->name ?? '',
            default => '',
        };
    }
}
