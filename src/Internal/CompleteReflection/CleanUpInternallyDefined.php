<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Hook\ClassHook;
use Typhoon\Reflection\Internal\Hook\ConstantHook;
use Typhoon\Reflection\Internal\Hook\FunctionHook;
use Typhoon\Reflection\Internal\Hook\HookPriorities;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum CleanUpInternallyDefined implements ConstantHook, FunctionHook, ClassHook
{
    case Instance;

    public function priority(): int
    {
        return HookPriorities::CLEAN_UP;
    }

    public function processConstant(ConstantId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        if (!$data[Data::InternallyDefined]) {
            return $data;
        }

        return self::cleanUp($data);
    }

    public function processFunction(NamedFunctionId|AnonymousFunctionId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        if (!$data[Data::InternallyDefined]) {
            return $data;
        }

        return self::cleanUpFunctionLike($data);
    }

    public function processClass(NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        if (!$data[Data::InternallyDefined]) {
            return $data;
        }

        return self::cleanUp($data)
            ->with(Data::Constants, array_map(self::cleanUp(...), $data[Data::Constants]))
            ->with(Data::Properties, array_map(self::cleanUp(...), $data[Data::Properties]))
            ->with(Data::Methods, array_map(self::cleanUpFunctionLike(...), $data[Data::Methods]));
    }

    private static function cleanUpFunctionLike(TypedMap $data): TypedMap
    {
        return self::cleanUp($data)
            ->with(Data::Parameters, array_map(self::cleanUp(...), $data[Data::Parameters]));
    }

    private static function cleanUp(TypedMap $data): TypedMap
    {
        return $data->without(Data::Location, Data::PhpDoc);
    }
}
