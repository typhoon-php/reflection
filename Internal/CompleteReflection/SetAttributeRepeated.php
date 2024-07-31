<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Hook\ClassHook;
use Typhoon\Reflection\Internal\Hook\FunctionHook;
use Typhoon\Reflection\Internal\Hook\HookPriorities;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum SetAttributeRepeated implements FunctionHook, ClassHook
{
    case Instance;

    public function priority(): int
    {
        return HookPriorities::COMPLETE_REFLECTION;
    }

    public function processFunction(NamedFunctionId|AnonymousFunctionId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        return self::processFunctionLike($data);
    }

    public function processClass(NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        return self::processAttributes($data)
            ->with(Data::Constants, array_map(self::processAttributes(...), $data[Data::Constants]))
            ->with(Data::Properties, array_map(self::processAttributes(...), $data[Data::Properties]))
            ->with(Data::Methods, array_map(self::processFunctionLike(...), $data[Data::Methods]));
    }

    private static function processFunctionLike(TypedMap $data): TypedMap
    {
        return self::processAttributes($data)
            ->with(Data::Parameters, array_map(self::processAttributes(...), $data[Data::Parameters]));
    }

    private static function processAttributes(TypedMap $data): TypedMap
    {
        $attributes = $data[Data::Attributes];

        if ($attributes === []) {
            return $data;
        }

        $repeated = [];

        foreach ($attributes as $attribute) {
            $class = $attribute[Data::AttributeClassName];
            $repeated[$class] = isset($repeated[$class]);
        }

        return $data->with(Data::Attributes, array_map(
            static fn(TypedMap $attribute): TypedMap => $attribute->with(
                Data::AttributeRepeated,
                $repeated[$attribute[Data::AttributeClassName]],
            ),
            $attributes,
        ));
    }
}
