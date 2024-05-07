<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\Reflection;
use Typhoon\Reflection\Reflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ConstantFetch implements Expression
{
    /**
     * @param non-empty-string $namespacedName
     */
    public function __construct(
        private readonly string $namespacedName,
        private readonly ?string $globalName,
    ) {}

    public function evaluate(Reflection $reflection, Reflector $reflector): mixed
    {
        // todo via reflection
        if ($this->globalName === null || \defined($this->namespacedName)) {
            return \constant($this->namespacedName);
        }

        return \constant($this->globalName);
    }
}
