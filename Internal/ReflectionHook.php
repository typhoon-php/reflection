<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\Reflection\Reflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface ReflectionHook
{
    public function reflect(FunctionId|ClassId|AnonymousClassId $id, TypedMap $data, Reflector $reflector): TypedMap;
}
