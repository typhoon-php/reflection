<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\Reflection\ClassReflection;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface ClassReflector
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function reflectClass(string $name): ClassReflection;
}
