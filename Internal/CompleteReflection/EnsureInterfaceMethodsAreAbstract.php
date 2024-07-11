<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\ClassKind;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\DataReflector;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class EnsureInterfaceMethodsAreAbstract implements ReflectionHook
{
    public function reflect(NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, DataReflector $reflector): TypedMap
    {
        if ($id instanceof NamedFunctionId
            || $id instanceof AnonymousFunctionId
            || $data[Data::ClassKind] !== ClassKind::Interface) {
            return $data;
        }

        return $data->modifyIfSet(Data::Methods, static fn(array $methods): array => array_map(
            static fn(TypedMap $method): TypedMap => $method->set(Data::Abstract, true),
            $methods,
        ));
    }
}
