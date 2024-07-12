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
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\DataReflector;
use Typhoon\Reflection\Internal\ReflectionHook\ClassReflectionHook;
use Typhoon\Reflection\Internal\ReflectionHook\ConstantReflectionHook;
use Typhoon\Reflection\Internal\ReflectionHook\FunctionReflectionHook;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ResolveChangeDetector implements ConstantReflectionHook, FunctionReflectionHook, ClassReflectionHook
{
    public function process(ConstantId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, DataReflector $reflector): TypedMap
    {
        $unresolved = $data[Data::UnresolvedChangeDetectors];

        return $data->set(Data::ChangeDetector, $unresolved === [] ? new InMemoryChangeDetector() : ChangeDetectors::from($unresolved));
    }
}
