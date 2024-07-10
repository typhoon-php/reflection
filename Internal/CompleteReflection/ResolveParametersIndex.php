<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ResolveParametersIndex implements ReflectionHook
{
    public function reflect(FunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data): TypedMap
    {
        if ($id instanceof FunctionId) {
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
