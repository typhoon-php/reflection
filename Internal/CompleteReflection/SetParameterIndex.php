<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\ClassHook;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\FunctionHook;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum SetParameterIndex implements FunctionHook, ClassHook
{
    case Instance;

    public function process(NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        if ($id instanceof NamedFunctionId || $id instanceof AnonymousFunctionId) {
            return self::processParameters($data);
        }

        return $data->with(Data::Methods, array_map(self::processParameters(...), $data[Data::Methods]));
    }

    private static function processParameters(TypedMap $data): TypedMap
    {
        return $data->with(Data::Parameters, array_map(
            static function (TypedMap $parameter): TypedMap {
                /** @var non-negative-int */
                static $index = 0;

                return $parameter->with(Data::Index, $index++);
            },
            $data[Data::Parameters],
        ));
    }
}
