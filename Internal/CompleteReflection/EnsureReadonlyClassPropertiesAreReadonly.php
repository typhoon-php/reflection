<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\ClassKind;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\ReflectionHook\ClassReflectionHook;
use Typhoon\Reflection\Internal\Reflector;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class EnsureReadonlyClassPropertiesAreReadonly implements ClassReflectionHook
{
    public function process(NamedClassId|AnonymousClassId $id, TypedMap $data, Reflector $reflector): TypedMap
    {
        if ($data[Data::ClassKind] !== ClassKind::Class_) {
            return $data;
        }

        if ($data[Data::NativeReadonly]) {
            $data = $data->withModifiedIfSet(Data::Properties, static fn(array $properties): array => array_map(
                static fn(TypedMap $property): TypedMap => $property->with(Data::NativeReadonly, true),
                $properties,
            ));
        }

        if ($data[Data::AnnotatedReadonly]) {
            $data = $data->withModifiedIfSet(Data::Properties, static fn(array $properties): array => array_map(
                static fn(TypedMap $property): TypedMap => $property->with(Data::AnnotatedReadonly, true),
                $properties,
            ));
        }

        return $data;
    }
}
