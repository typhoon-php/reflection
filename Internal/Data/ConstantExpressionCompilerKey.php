<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Reflection\Internal\ConstantExpression\ConstantExpressionCompiler;
use Typhoon\Reflection\Internal\TypedMap\Key;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements Key<ConstantExpressionCompiler>
 */
enum ConstantExpressionCompilerKey implements Key
{
    case Key;
}
