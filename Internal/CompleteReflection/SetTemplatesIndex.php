<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\ReflectionHook\ClassReflectionHook;
use Typhoon\Reflection\Internal\ReflectionHook\FunctionReflectionHook;
use Typhoon\Reflection\Internal\Reflector;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class SetTemplatesIndex implements FunctionReflectionHook, ClassReflectionHook
{
    public function process(NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, Reflector $reflector): TypedMap
    {
        return self::processTemplates($data)->with(Data::Methods, array_map(self::processTemplates(...), $data[Data::Methods]));
    }

    private static function processTemplates(TypedMap $data): TypedMap
    {
        return $data->with(Data::Templates, array_map(
            static function (TypedMap $parameter): TypedMap {
                /** @var non-negative-int */
                static $index = 0;

                return $parameter->with(Data::Index, $index++);
            },
            $data[Data::Templates],
        ));
    }
}
