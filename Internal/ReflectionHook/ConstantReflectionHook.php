<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ReflectionHook;

use Typhoon\DeclarationId\ConstantId;
use Typhoon\Reflection\Internal\Reflector;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface ConstantReflectionHook
{
    public function process(ConstantId $id, TypedMap $data, Reflector $reflector): TypedMap;
}
