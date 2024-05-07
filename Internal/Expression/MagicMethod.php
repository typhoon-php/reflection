<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\MethodReflection;
use Typhoon\Reflection\Reflection;
use Typhoon\Reflection\Reflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum MagicMethod implements Expression
{
    case Constant;

    public function evaluate(Reflection $reflection, Reflector $reflector): string
    {
        if ($reflection instanceof MethodReflection) {
            return $reflection->class()->name . '::' . $reflection->name;
        }

        return '';
    }
}
