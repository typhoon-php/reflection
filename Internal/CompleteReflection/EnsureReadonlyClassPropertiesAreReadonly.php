<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\ClassKind;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class EnsureReadonlyClassPropertiesAreReadonly implements ReflectionHook
{
    public function reflect(FunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data): TypedMap
    {
        if (($data[Data::ClassKind] ?? null) !== ClassKind::Class_) {
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
