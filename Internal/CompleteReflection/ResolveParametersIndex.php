<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\DataReflector;
use Typhoon\Reflection\Internal\ReflectionHook\ClassReflectionHook;
use Typhoon\Reflection\Internal\ReflectionHook\FunctionReflectionHook;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ResolveParametersIndex implements FunctionReflectionHook, ClassReflectionHook
{
    public function process(NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, DataReflector $reflector): TypedMap
    {
        if ($id instanceof NamedFunctionId || $id instanceof AnonymousFunctionId) {
            return $this->resolveParametersIndex($data);
        }

        return $data->modifyIfSet(Data::Methods, fn(array $methods): array => array_map(
            $this->resolveParametersIndex(...),
            $methods,
        ));
    }

    private function resolveParametersIndex(TypedMap $data): TypedMap
    {
        return $data->modifyIfSet(Data::Parameters, static fn(array $parameters): array => array_map(
            static function (TypedMap $parameter): TypedMap {
                /** @var non-negative-int */
                static $index = 0;

                return $parameter->set(Data::Index, $index++);
            },
            $parameters,
        ));
    }
}
