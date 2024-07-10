<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\ClassKind;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class EnsureInterfaceMethodsAreAbstract implements ReflectionHook
{
    public function reflect(FunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data): TypedMap
    {
        if ($id instanceof FunctionId || $data[Data::ClassKind] !== ClassKind::Interface) {
            return $data;
        }

        return $data->modifyIfSet(Data::Methods, static fn(array $methods): array => array_map(
            static fn(TypedMap $method): TypedMap => $method->set(Data::Abstract, true),
            $methods,
        ));
    }
}
