<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\Reflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ArrayExpression implements Expression
{
    /**
     * @param non-empty-list<ArrayElement> $elements
     */
    public function __construct(
        private readonly array $elements,
    ) {}

    public function evaluate(?Reflector $reflector = null): mixed
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
