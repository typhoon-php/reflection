<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Hook;

use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface FunctionHook
{
    public function priority(): int;

    public function processFunction(NamedFunctionId|AnonymousFunctionId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap;
}
