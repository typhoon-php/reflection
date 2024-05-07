<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\Reflection;
use Typhoon\Reflection\Reflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class Value implements Expression
{
    public function __construct(
        private readonly null|bool|int|float|string|array $value,
    ) {}

    public function evaluate(Reflection $reflection, Reflector $reflector): mixed
    {
        return $this->value;
    }
}
