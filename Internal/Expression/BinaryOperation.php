<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\Internal\ClassReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class BinaryOperation implements Expression
{
    public function __construct(
        private readonly Expression $left,
        private readonly Expression $right,
        private readonly string $operator,
    ) {}

    public function evaluate(ClassReflector $classReflector): mixed
    {
        /** @psalm-suppress MixedOperand */
        return match ($this->operator) {
            '&' => $this->left->evaluate($classReflector) & $this->right->evaluate($classReflector),
            '|' => $this->left->evaluate($classReflector) | $this->right->evaluate($classReflector),
            '^' => $this->left->evaluate($classReflector) ^ $this->right->evaluate($classReflector),
            '&&' => $this->left->evaluate($classReflector) && $this->right->evaluate($classReflector),
            '||' => $this->left->evaluate($classReflector) || $this->right->evaluate($classReflector),
            '??' => $this->left->evaluate($classReflector) ?? $this->right->evaluate($classReflector),
            '.' => $this->left->evaluate($classReflector) . $this->right->evaluate($classReflector),
            '/' => $this->left->evaluate($classReflector) / $this->right->evaluate($classReflector),
            '==' => $this->left->evaluate($classReflector) == $this->right->evaluate($classReflector),
            '>' => $this->left->evaluate($classReflector) > $this->right->evaluate($classReflector),
            '>=' => $this->left->evaluate($classReflector) >= $this->right->evaluate($classReflector),
            '===' => $this->left->evaluate($classReflector) === $this->right->evaluate($classReflector),
            'and' => $this->left->evaluate($classReflector) and $this->right->evaluate($classReflector),
            'or' => $this->left->evaluate($classReflector) or $this->right->evaluate($classReflector),
            'xor' => $this->left->evaluate($classReflector) xor $this->right->evaluate($classReflector),
            '-' => $this->left->evaluate($classReflector) - $this->right->evaluate($classReflector),
            '%' => $this->left->evaluate($classReflector) % $this->right->evaluate($classReflector),
            '*' => $this->left->evaluate($classReflector) * $this->right->evaluate($classReflector),
            '!=' => $this->left->evaluate($classReflector) != $this->right->evaluate($classReflector),
            '!==' => $this->left->evaluate($classReflector) !== $this->right->evaluate($classReflector),
            '+' => $this->left->evaluate($classReflector) + $this->right->evaluate($classReflector),
            '**' => $this->left->evaluate($classReflector) ** $this->right->evaluate($classReflector),
            '<<' => $this->left->evaluate($classReflector) << $this->right->evaluate($classReflector),
            '>>' => $this->left->evaluate($classReflector) >> $this->right->evaluate($classReflector),
            '<' => $this->left->evaluate($classReflector) < $this->right->evaluate($classReflector),
            '<=' => $this->left->evaluate($classReflector) <= $this->right->evaluate($classReflector),
            '<=>' => $this->left->evaluate($classReflector) <=> $this->right->evaluate($classReflector),
        };
    }
}
