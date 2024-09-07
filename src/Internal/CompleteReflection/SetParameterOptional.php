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
enum SetParameterOptional implements FunctionHook, ClassHook
{
    case Instance;

    public function priority(): int
    {
        return HookPriorities::COMPLETE_REFLECTION;
    }

    public function processFunction(NamedFunctionId|AnonymousFunctionId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        return self::processParameters($data);
    }

    public function processClass(NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        return $data->with(Data::Methods, array_map(self::processParameters(...), $data[Data::Methods]));
    }

    private static function processParameters(TypedMap $data): TypedMap
    {
        return $data->with(Data::Parameters, array_map(
            static fn(TypedMap $parameter): TypedMap => $parameter->with(Data::Optional, self::isOptional($parameter)),
            $data[Data::Parameters],
        ));
    }

    private static function isOptional(TypedMap $parameter): bool
    {
        return $parameter[Data::Optional]
            || $parameter[Data::DefaultValueExpression]
            || $parameter[Data::Variadic];
    }
}
