<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Inheritance;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Hook\ClassHook;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum ResolveClassInheritance implements ClassHook
{
    case Instance;

    public function priority(): int
    {
        return -500;
    }

    public function processClass(NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        return ClassInheritance::resolve($id, $data, $reflector);
    }
}
