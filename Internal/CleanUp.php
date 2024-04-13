<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class CleanUp implements ReflectionHook
{
    public function reflect(ClassId|AnonymousClassId|FunctionId $id, TypedMap $data): TypedMap
    {
        return $data->without(
            Data::TypeContext(),
            Data::UnresolvedChangeDetectors(),
            Data::UnresolvedInterfaces(),
            Data::UnresolvedTraits(),
            Data::UnresolvedParent(),
        );
    }
}
