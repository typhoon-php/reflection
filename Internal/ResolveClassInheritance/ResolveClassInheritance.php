<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ResolveClassInheritance;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\ClassReflectionHook;
use Typhoon\Reflection\Internal\DataReflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ResolveClassInheritance implements ClassReflectionHook
{
    public function process(NamedClassId|AnonymousClassId $id, TypedMap $data, DataReflector $reflector): TypedMap
    {
        return ClassInheritanceResolver::resolve($reflector, $id, $data);
    }
}
