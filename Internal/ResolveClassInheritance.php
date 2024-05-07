<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\Reflection\Internal\ClassInheritance\ClassInheritanceResolver;
use Typhoon\Reflection\Reflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ResolveClassInheritance implements ReflectionHook
{
    public function reflect(FunctionId|ClassId|AnonymousClassId $id, TypedMap $data, Reflector $reflector): TypedMap
    {
        if ($id instanceof FunctionId) {
            return $data;
        }

        return ClassInheritanceResolver::resolve($reflector, $id, $data);
    }
}
