<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ReflectionHook;

use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Reflector;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface FunctionReflectionHook
{
    public function process(NamedFunctionId|AnonymousFunctionId $id, TypedMap $data, Reflector $reflector): TypedMap;
}
