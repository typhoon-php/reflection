<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
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
    public function reflect(FunctionId|ClassId|AnonymousClassId $id, TypedMap $data): TypedMap
    {
        if (($data[Data::ClassKind] ?? null) !== ClassKind::Interface) {
            return $data;
        }

        return $data->modify(Data::Methods, static fn(array $methods): array => array_map(
            static fn(TypedMap $method): TypedMap => $method->set(Data::Abstract, true),
            $methods,
        ));
    }
}
