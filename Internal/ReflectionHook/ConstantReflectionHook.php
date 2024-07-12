<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ReflectionHook;

use Typhoon\DeclarationId\ConstantId;
use Typhoon\Reflection\Internal\DataReflector;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface ConstantReflectionHook
{
    public function process(ConstantId $id, TypedMap $data, DataReflector $reflector): TypedMap;
}
