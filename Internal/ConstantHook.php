<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\ConstantId;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface ConstantHook
{
    public function process(ConstantId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap;
}
