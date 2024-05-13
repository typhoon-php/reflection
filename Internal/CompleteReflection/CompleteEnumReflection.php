<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\Reflection\Internal\ClassKind;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\InheritedName;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\Reflection\Internal\Visibility;
use Typhoon\Type\types;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class CompleteEnumReflection implements ReflectionHook
{
    public function reflect(ClassId|AnonymousClassId|FunctionId $id, TypedMap $data): TypedMap
    {
        if ($id instanceof FunctionId) {
            return $data;
        }

        if ($data[Data::ClassKind()] !== ClassKind::Enum) {
            return $data;
        }

        $scalarType = $data[Data::NativeType()];
        $interfaces = $data[Data::UnresolvedInterfaces()];
        $properties = [];
        $methods = $data[Data::Methods()];
        $staticType = types::static($id);

        $interfaces[] = new InheritedName(\UnitEnum::class);

        $properties['name'] = (new TypedMap())
            ->set(Data::NativeReadonly(), true)
            ->set(Data::NativeType(), types::string)
            ->set(Data::Visibility(), Visibility::Public);

        $methods['cases'] = (new TypedMap())
            ->set(Data::Static(), true)
            ->set(Data::NativeType(), types::array)
            ->set(Data::AnnotatedType(), types::list($staticType))
            ->set(Data::Visibility(), Visibility::Public)
            ->set(Data::WrittenInC(), true);

        if ($scalarType !== null) {
            $interfaces[] = new InheritedName(\BackedEnum::class);

            $properties['value'] = (new TypedMap())
                ->set(Data::NativeReadonly(), true)
                ->set(Data::NativeType(), $scalarType)
                ->set(Data::Visibility(), Visibility::Public);

            $methods['from'] = $methods['cases']
                ->set(Data::NativeType(), $staticType)
                ->set(Data::Parameters(), [
                    'value' => (new TypedMap())
                        ->set(Data::NativeType(), types::arrayKey)
                        ->set(Data::AnnotatedType(), $scalarType),
                ]);

            $methods['tryFrom'] = $methods['from']
                ->set(Data::NativeType(), types::nullable($staticType));
        }

        return $data
            ->set(Data::UnresolvedInterfaces(), $interfaces)
            ->set(Data::Properties(), $properties)
            ->set(Data::Methods(), $methods);
    }
}
