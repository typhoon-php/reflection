<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\ClassHook;
use Typhoon\Reflection\Internal\ConstantHook;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\FunctionHook;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum CleanUpInternallyDefined implements ConstantHook, FunctionHook, ClassHook
{
    case Instance;

    public function process(ConstantId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        if (!$data[Data::InternallyDefined]) {
            return $data;
        }

        return self::cleanUp($data)
            ->with(Data::Constants, array_map(self::cleanUp(...), $data[Data::Constants]))
            ->with(Data::Properties, array_map(self::cleanUp(...), $data[Data::Properties]))
            ->with(Data::Methods, array_map(self::cleanUp(...), $data[Data::Methods]));
    }

    private static function cleanUp(TypedMap $data): TypedMap
    {
        return $data
            ->without(Data::Location, Data::PhpDoc)
            ->with(Data::Parameters, array_map(self::cleanUp(...), $data[Data::Parameters]));
    }
}
