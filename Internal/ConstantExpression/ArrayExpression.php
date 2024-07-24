<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<array>
 */
final class ArrayExpression implements Expression
{
    /**
     * @param non-empty-list<ArrayElement> $elements
     */
    public function __construct(
        private readonly array $elements,
    ) {}

    public function recompile(CompilationContext $context): Expression
    {
        return new self(array_map(
            static fn(ArrayElement $element): ArrayElement => new ArrayElement(
                key: $element->key instanceof Expression ? $element->key->recompile($context) : $element->key,
                value: $element->value->recompile($context),
            ),
            $this->elements,
        ));
    }

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        $array = [];

        foreach ($this->elements as $element) {
            $value = $element->value->evaluate($reflector);

            if ($element->key === null) {
                $array[] = $value;

                continue;
            }

            if ($element->key === true) {
                /** @psalm-suppress InvalidOperand */
                $array = [...$array, ...$value];

                continue;
            }

            /** @psalm-suppress MixedArrayOffset */
            $array[$element->key->evaluate($reflector)] = $value;
        }

        return $array;
    }
}
