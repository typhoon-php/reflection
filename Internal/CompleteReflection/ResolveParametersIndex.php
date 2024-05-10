<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ResolveParametersIndex implements ReflectionHook
{
    public function reflect(FunctionId|ClassId|AnonymousClassId $id, TypedMap $data): TypedMap
    {
        if ($id instanceof FunctionId) {
            return $this->resolveParametersIndex($data);
        }

        if (isset($data[Data::Methods()])) {
            return $data->with(Data::Methods(), array_map(
                $this->resolveParametersIndex(...),
                $data[Data::Methods()],
            ));
        }

        return $data;
    }

    private function resolveParametersIndex(TypedMap $data): TypedMap
    {
        $parameters = $data[Data::Parameters()] ?? [];

        if ($parameters === []) {
            return $data;
        }

        $resolvedParameters = [];
        $index = 0;

        foreach ($parameters as $name => $parameter) {
            $resolvedParameters[$name] = $parameter->with(Data::Index(), $index++);
        }

        return $data->with(Data::Parameters(), $resolvedParameters);
    }
}
