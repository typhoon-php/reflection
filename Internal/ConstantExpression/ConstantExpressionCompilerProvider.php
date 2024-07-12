<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface ConstantExpressionCompilerProvider
{
    public function get(): ConstantExpressionCompiler;
}
