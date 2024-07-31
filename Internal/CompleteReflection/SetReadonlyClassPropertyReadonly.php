<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\ClassKind;
use Typhoon\Reflection\Internal\Hook\ClassHook;
use Typhoon\Reflection\Internal\Hook\HookPriorities;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum SetReadonlyClassPropertyReadonly implements ClassHook
{
    case Instance;

    public function priority(): int
    {
        return HookPriorities::COMPLETE_REFLECTION;
    }

    public function processClass(NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        if ($data[Data::ClassKind] !== ClassKind::Class_) {
            return $data;
        }

        if ($data[Data::NativeReadonly]) {
            $data = $data->with(Data::Properties, array_map(
                static fn(TypedMap $property): TypedMap => $property->with(Data::NativeReadonly, true),
                $data[Data::Properties],
            ));
        }

        if ($data[Data::AnnotatedReadonly]) {
            $data = $data->with(Data::Properties, array_map(
                static fn(TypedMap $property): TypedMap => $property->with(Data::AnnotatedReadonly, true),
                $data[Data::Properties],
            ));
        }

        return $data;
    }
}
