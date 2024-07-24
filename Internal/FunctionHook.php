<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface FunctionHook
{
    public function process(NamedFunctionId|AnonymousFunctionId $id, TypedMap $data): TypedMap;
}
