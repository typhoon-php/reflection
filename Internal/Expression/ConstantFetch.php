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
     * @param non-empty-string $name
     * @param ?non-empty-string $globalName
     */
    public function __construct(
        private readonly string $name,
        private readonly ?string $globalName = null,
    ) {}

    /**
     * @return non-empty-string
     */
    public function name(Reflector $_reflector): string
    {
        if ($this->globalName === null || \defined($this->name)) {
            return $this->name;
        }

        return $this->globalName;
    }

    public function evaluate(Reflection $reflection, Reflector $reflector): mixed
    {
        // todo via reflection
        return \constant($this->name($reflector));
    }
}
