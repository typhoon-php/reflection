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
enum MagicFile implements Expression
{
    case Constant;

    public function evaluate(Reflection $reflection, Reflector $reflector): string
    {
        return match (true) {
            $reflection instanceof ClassReflection,
            $reflection instanceof ClassConstantReflection,
            $reflection instanceof PropertyReflection,
            $reflection instanceof MethodReflection => $reflection->file() ?? '',
            default => '',
        };
    }
}
