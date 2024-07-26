<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\ConstantId;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface ConstantHook
{
    public function process(ConstantId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap;
}
