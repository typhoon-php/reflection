<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Inheritance;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\DataReflector;
use Typhoon\Reflection\Internal\ReflectionHook\ClassReflectionHook;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ResolveClassInheritance implements ClassReflectionHook
{
    public function process(NamedClassId|AnonymousClassId $id, TypedMap $data, DataReflector $reflector): TypedMap
    {
        return ClassInheritance::resolve($reflector, $id, $data);
    }
}
