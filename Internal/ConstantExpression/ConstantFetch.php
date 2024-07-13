<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<mixed>
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
    public function name(?TyphoonReflector $_reflector = null): string
    {
        if ($this->globalName === null || \defined($this->name)) {
            return $this->name;
        }

        return $this->globalName;
    }

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        // todo via reflection
        return \constant($this->name($reflector));
    }
}
