<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\DataReflector;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class CleanUp implements ReflectionHook
{
    public function reflect(FunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, DataReflector $reflector): TypedMap
    {
        return $data->unset(
            Data::TypeContext,
            Data::UnresolvedChangeDetectors,
            Data::UnresolvedInterfaces,
            Data::UnresolvedParent,
            Data::UnresolvedTraits,
            Data::UsePhpDocs,
            Data::TraitMethodAliases,
            Data::TraitMethodPrecedence,
        );
    }
}
