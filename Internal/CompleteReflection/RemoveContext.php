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
enum RemoveContext implements ConstantHook, FunctionHook, ClassHook
{
    case Instance;

    public function process(ConstantId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        return $data
            ->without(Data::Context)
            ->with(Data::Methods, array_map(
                static fn(TypedMap $data): TypedMap => $data->without(Data::Context),
                $data[Data::Methods],
            ));
    }
}
