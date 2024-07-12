<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\ClassKind;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\DataReflector;
use Typhoon\Reflection\Internal\ReflectionHook\ClassReflectionHook;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class EnsureInterfaceMethodsAreAbstract implements ClassReflectionHook
{
    public function process(NamedClassId|AnonymousClassId $id, TypedMap $data, DataReflector $reflector): TypedMap
    {
        if ($data[Data::ClassKind] !== ClassKind::Interface) {
            return $data;
        }

        return $data->modifyIfSet(Data::Methods, static fn(array $methods): array => array_map(
            static fn(TypedMap $method): TypedMap => $method->set(Data::Abstract, true),
            $methods,
        ));
    }
}
