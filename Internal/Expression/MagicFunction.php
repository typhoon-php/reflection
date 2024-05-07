<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\Reflection;
use Typhoon\Reflection\Reflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum MagicFunction implements Expression
{
    case Constant;

    public function evaluate(Reflection $reflection, Reflector $reflector): string
    {
        return '';
    }
}
