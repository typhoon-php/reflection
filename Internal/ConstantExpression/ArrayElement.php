<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ArrayElement
{
    /**
     * @param null|true|Expression $key
     */
    public function __construct(
        public readonly null|bool|Expression $key,
        public readonly Expression $value,
    ) {}
}
