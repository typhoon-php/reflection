<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

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

    public function evaluate(EvaluationContext $context): mixed
    {
        /** @psalm-suppress MixedOperand */
        return match ($this->operator) {
            '&' => $this->left->evaluate($context) & $this->right->evaluate($context),
            '|' => $this->left->evaluate($context) | $this->right->evaluate($context),
            '^' => $this->left->evaluate($context) ^ $this->right->evaluate($context),
            '&&' => $this->left->evaluate($context) && $this->right->evaluate($context),
            '||' => $this->left->evaluate($context) || $this->right->evaluate($context),
            '??' => $this->left->evaluate($context) ?? $this->right->evaluate($context),
            '.' => $this->left->evaluate($context) . $this->right->evaluate($context),
            '/' => $this->left->evaluate($context) / $this->right->evaluate($context),
            '==' => $this->left->evaluate($context) == $this->right->evaluate($context),
            '>' => $this->left->evaluate($context) > $this->right->evaluate($context),
            '>=' => $this->left->evaluate($context) >= $this->right->evaluate($context),
            '===' => $this->left->evaluate($context) === $this->right->evaluate($context),
            'and' => $this->left->evaluate($context) and $this->right->evaluate($context),
            'or' => $this->left->evaluate($context) or $this->right->evaluate($context),
            'xor' => $this->left->evaluate($context) xor $this->right->evaluate($context),
            '-' => $this->left->evaluate($context) - $this->right->evaluate($context),
            '%' => $this->left->evaluate($context) % $this->right->evaluate($context),
            '*' => $this->left->evaluate($context) * $this->right->evaluate($context),
            '!=' => $this->left->evaluate($context) != $this->right->evaluate($context),
            '!==' => $this->left->evaluate($context) !== $this->right->evaluate($context),
            '+' => $this->left->evaluate($context) + $this->right->evaluate($context),
            '**' => $this->left->evaluate($context) ** $this->right->evaluate($context),
            '<<' => $this->left->evaluate($context) << $this->right->evaluate($context),
            '>>' => $this->left->evaluate($context) >> $this->right->evaluate($context),
            '<' => $this->left->evaluate($context) < $this->right->evaluate($context),
            '<=' => $this->left->evaluate($context) <= $this->right->evaluate($context),
            '<=>' => $this->left->evaluate($context) <=> $this->right->evaluate($context),
        };
    }
}
