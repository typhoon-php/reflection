<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<mixed>
 */
final class BinaryOperation implements Expression
{
    public function __construct(
        private readonly Expression $left,
        private readonly Expression $right,
        private readonly string $operator,
    ) {}

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        /** @psalm-suppress MixedOperand */
        return match ($this->operator) {
            '&' => $this->left->evaluate($reflector) & $this->right->evaluate($reflector),
            '|' => $this->left->evaluate($reflector) | $this->right->evaluate($reflector),
            '^' => $this->left->evaluate($reflector) ^ $this->right->evaluate($reflector),
            '&&' => $this->left->evaluate($reflector) && $this->right->evaluate($reflector),
            '||' => $this->left->evaluate($reflector) || $this->right->evaluate($reflector),
            '??' => $this->left->evaluate($reflector) ?? $this->right->evaluate($reflector),
            '.' => $this->left->evaluate($reflector) . $this->right->evaluate($reflector),
            '/' => $this->left->evaluate($reflector) / $this->right->evaluate($reflector),
            '==' => $this->left->evaluate($reflector) == $this->right->evaluate($reflector),
            '>' => $this->left->evaluate($reflector) > $this->right->evaluate($reflector),
            '>=' => $this->left->evaluate($reflector) >= $this->right->evaluate($reflector),
            '===' => $this->left->evaluate($reflector) === $this->right->evaluate($reflector),
            'and' => $this->left->evaluate($reflector) and $this->right->evaluate($reflector),
            'or' => $this->left->evaluate($reflector) or $this->right->evaluate($reflector),
            'xor' => $this->left->evaluate($reflector) xor $this->right->evaluate($reflector),
            '-' => $this->left->evaluate($reflector) - $this->right->evaluate($reflector),
            '%' => $this->left->evaluate($reflector) % $this->right->evaluate($reflector),
            '*' => $this->left->evaluate($reflector) * $this->right->evaluate($reflector),
            '!=' => $this->left->evaluate($reflector) != $this->right->evaluate($reflector),
            '!==' => $this->left->evaluate($reflector) !== $this->right->evaluate($reflector),
            '+' => $this->left->evaluate($reflector) + $this->right->evaluate($reflector),
            '**' => $this->left->evaluate($reflector) ** $this->right->evaluate($reflector),
            '<<' => $this->left->evaluate($reflector) << $this->right->evaluate($reflector),
            '>>' => $this->left->evaluate($reflector) >> $this->right->evaluate($reflector),
            '<' => $this->left->evaluate($reflector) < $this->right->evaluate($reflector),
            '<=' => $this->left->evaluate($reflector) <= $this->right->evaluate($reflector),
            '<=>' => $this->left->evaluate($reflector) <=> $this->right->evaluate($reflector),
        };
    }
}
