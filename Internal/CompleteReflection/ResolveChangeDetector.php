<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\ChangeDetector\ChangeDetectors;
use Typhoon\ChangeDetector\InMemoryChangeDetector;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\ClassHook;
use Typhoon\Reflection\Internal\ConstantHook;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\FunctionHook;
use Typhoon\Reflection\Internal\Reflector;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum ResolveChangeDetector implements ConstantHook, FunctionHook, ClassHook
{
    case Instance;

    public function process(ConstantId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, Reflector $reflector): TypedMap
    {
        $changeDetectors = $data[Data::UnresolvedChangeDetectors] ?: [new InMemoryChangeDetector()];

        return $data
            ->with(Data::ChangeDetector, ChangeDetectors::from($changeDetectors))
            ->without(Data::UnresolvedChangeDetectors);
    }
}
