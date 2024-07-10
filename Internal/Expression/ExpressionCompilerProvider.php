<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface ExpressionCompilerProvider
{
    public function get(): ExpressionCompiler;
}
