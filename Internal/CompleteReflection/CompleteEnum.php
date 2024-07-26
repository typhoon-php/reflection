<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\ClassHook;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\ClassKind;
use Typhoon\Reflection\Internal\Data\TypeData;
use Typhoon\Reflection\Internal\Data\Visibility;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Type\types;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum CompleteEnum implements ClassHook
{
    case Instance;

    public function process(NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        if ($data[Data::ClassKind] !== ClassKind::Enum) {
            return $data;
        }

        $backingType = $data[Data::BackingType];
        $interfaces = $data[Data::UnresolvedInterfaces];
        $properties = $data[Data::Properties];
        $methods = $data[Data::Methods];
        $staticType = types::static(resolvedClass: $id);

        $interfaces[\UnitEnum::class] = [];

        $properties['name'] = (new TypedMap())
            ->with(Data::NativeReadonly, true)
            ->with(Data::Type, new TypeData(types::string))
            ->with(Data::Visibility, Visibility::Public);

        $methods['cases'] = (new TypedMap())
            ->with(Data::Static, true)
            ->with(Data::Type, new TypeData(types::array, types::list($staticType)))
            ->with(Data::Visibility, Visibility::Public)
            ->with(Data::InternallyDefined, true);

        if ($backingType !== null) {
            $interfaces[\BackedEnum::class] = [];

            $properties['value'] = (new TypedMap())
                ->with(Data::NativeReadonly, true)
                ->with(Data::Type, new TypeData($backingType))
                ->with(Data::Visibility, Visibility::Public);

            $methods['from'] = $methods['cases']
                ->with(Data::Type, new TypeData($staticType))
                ->with(Data::Parameters, [
                    'value' => TypedMap::one(Data::Type, new TypeData(types::arrayKey, $backingType)),
                ]);

            $methods['tryFrom'] = $methods['from']
                ->with(Data::Type, new TypeData(types::nullable($staticType)));
        }

        return $data
            ->with(Data::UnresolvedInterfaces, $interfaces)
            ->with(Data::Properties, $properties)
            ->with(Data::Methods, $methods);
    }
}
