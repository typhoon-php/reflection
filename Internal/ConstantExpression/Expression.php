<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon
 * @template-covariant T
 */
interface Expression
{
    /**
     * @param non-empty-string $self
     * @param ?non-empty-string $parent
     * @return Expression<T>
     */
    public function recompile(string $self, ?string $parent): self;

    /**
     * @return T
     */
    public function evaluate(?TyphoonReflector $reflector = null): mixed;
}
