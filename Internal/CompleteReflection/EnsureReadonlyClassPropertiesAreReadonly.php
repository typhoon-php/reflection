<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\ClassKind;
use Typhoon\Reflection\Internal\ClassReflectionHook;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\DataReflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class EnsureReadonlyClassPropertiesAreReadonly implements ClassReflectionHook
{
    public function process(NamedClassId|AnonymousClassId $id, TypedMap $data, DataReflector $reflector): TypedMap
    {
        if ($data[Data::ClassKind] !== ClassKind::Class_) {
            return $data;
        }

        if ($data[Data::NativeReadonly]) {
            $data = $data->modifyIfSet(Data::Properties, static fn(array $properties): array => array_map(
                static fn(TypedMap $property): TypedMap => $property->set(Data::NativeReadonly, true),
                $properties,
            ));
        }

        if ($data[Data::AnnotatedReadonly]) {
            $data = $data->modifyIfSet(Data::Properties, static fn(array $properties): array => array_map(
                static fn(TypedMap $property): TypedMap => $property->set(Data::AnnotatedReadonly, true),
                $properties,
            ));
        }

        return $data;
    }
}
