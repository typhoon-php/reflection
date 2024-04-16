<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ConstantFetch implements Expression
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        private readonly string $name,
        private readonly ?string $global,
    ) {}

    public function evaluate(EvaluationContext $context): mixed
    {
        if ($this->global === null) {
            return $context->constant($this->name);
        }

        try {
            return $context->constant($this->name);
        } catch (\Throwable) {
            return $context->constant($this->global);
        }
    }
}
