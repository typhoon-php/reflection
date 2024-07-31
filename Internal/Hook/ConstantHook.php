<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Hook;

use Typhoon\DeclarationId\ConstantId;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface ConstantHook
{
    public function priority(): int;

    public function processConstant(ConstantId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap;
}
