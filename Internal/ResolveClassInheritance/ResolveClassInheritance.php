<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ResolveClassInheritance;

use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\Reflection\Reflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ResolveClassInheritance implements ReflectionHook
{
    public function __construct(
        private readonly Reflector $reflector,
    ) {}

    public function reflect(FunctionId|ClassId $id, TypedMap $data): TypedMap
    {
        if ($id instanceof FunctionId) {
            return $data;
        }

        return ClassInheritanceResolver::resolve($this->reflector, $id, $data);
    }
}
