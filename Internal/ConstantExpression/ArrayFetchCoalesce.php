<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<mixed>
 */
final class ArrayFetchCoalesce implements Expression
{
    public function __construct(
        private readonly Expression $array,
        private readonly Expression $key,
        private readonly Expression $default,
    ) {}

    public function recompile(string $self, ?string $parent): Expression
    {
        return new self(
            array: $this->array->recompile($self, $parent),
            key: $this->key->recompile($self, $parent),
            default: $this->default->recompile($self, $parent),
        );
    }

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        /** @psalm-suppress MixedArrayOffset */
        return $this->array->evaluate($reflector)[$this->key->evaluate($reflector)] ?? $this->default->evaluate($reflector);
    }
}
