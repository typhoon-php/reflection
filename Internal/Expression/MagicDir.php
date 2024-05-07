<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\Reflection;
use Typhoon\Reflection\Reflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum MagicDir implements Expression
{
    case Constant;

    public function evaluate(Reflection $reflection, Reflector $reflector): string
    {
        return \dirname(MagicFile::Constant->evaluate($reflection, $reflector));
    }
}
