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
enum MagicTrait implements Expression
{
    case Constant;

    public function evaluate(Reflection $reflection, Reflector $reflector): string
    {
        $classReflection = match (true) {
            $reflection instanceof ClassReflection => $reflection,
            $reflection instanceof ClassConstantReflection => $reflection->class(),
            $reflection instanceof PropertyReflection => $reflection->class(),
            $reflection instanceof MethodReflection => $reflection->class(),
            $reflection instanceof ParameterReflection => $reflection->class(),
            default => null,
        };

        if ($classReflection !== null && $classReflection->isTrait()) {
            return $classReflection->name;
        }

        return '';
    }
}
