<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\Type\types;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class CompleteEnum implements ReflectionHook
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
            ->with(Data::NativeReadonly(), true)
            ->with(Data::NativeType(), types::string)
            ->with(Data::Visibility(), Visibility::Public);

        $methods['cases'] = (new TypedMap())
            ->with(Data::Static(), true)
            ->with(Data::NativeType(), types::array)
            ->with(Data::AnnotatedType(), types::list($staticType))
            ->with(Data::Visibility(), Visibility::Public)
            ->with(Data::WrittenInC(), true);

        if ($scalarType !== null) {
            $interfaces[] = new InheritedName(\BackedEnum::class);

            $properties['value'] = (new TypedMap())
                ->with(Data::NativeReadonly(), true)
                ->with(Data::NativeType(), $scalarType)
                ->with(Data::Visibility(), Visibility::Public);

            $methods['from'] = $methods['cases']
                ->with(Data::NativeType(), $staticType)
                ->with(Data::Parameters(), [
                    'value' => (new TypedMap())
                        ->with(Data::NativeType(), types::arrayKey)
                        ->with(Data::AnnotatedType(), $scalarType),
                ]);

            $methods['tryFrom'] = $methods['from']
                ->with(Data::NativeType(), types::nullable($staticType));
        }

        return $data
            ->with(Data::UnresolvedInterfaces(), $interfaces)
            ->with(Data::Properties(), $properties)
            ->with(Data::Methods(), $methods);
    }
}
