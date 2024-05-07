<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\Reflection;
use Typhoon\Reflection\Reflector;

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

    public function evaluate(Reflection $reflection, Reflector $reflector): mixed
    {
        /** @psalm-suppress MixedOperand */
        return match ($this->operator) {
            '&' => $this->left->evaluate($reflection, $reflector) & $this->right->evaluate($reflection, $reflector),
            '|' => $this->left->evaluate($reflection, $reflector) | $this->right->evaluate($reflection, $reflector),
            '^' => $this->left->evaluate($reflection, $reflector) ^ $this->right->evaluate($reflection, $reflector),
            '&&' => $this->left->evaluate($reflection, $reflector) && $this->right->evaluate($reflection, $reflector),
            '||' => $this->left->evaluate($reflection, $reflector) || $this->right->evaluate($reflection, $reflector),
            '??' => $this->left->evaluate($reflection, $reflector) ?? $this->right->evaluate($reflection, $reflector),
            '.' => $this->left->evaluate($reflection, $reflector) . $this->right->evaluate($reflection, $reflector),
            '/' => $this->left->evaluate($reflection, $reflector) / $this->right->evaluate($reflection, $reflector),
            '==' => $this->left->evaluate($reflection, $reflector) == $this->right->evaluate($reflection, $reflector),
            '>' => $this->left->evaluate($reflection, $reflector) > $this->right->evaluate($reflection, $reflector),
            '>=' => $this->left->evaluate($reflection, $reflector) >= $this->right->evaluate($reflection, $reflector),
            '===' => $this->left->evaluate($reflection, $reflector) === $this->right->evaluate($reflection, $reflector),
            'and' => $this->left->evaluate($reflection, $reflector) and $this->right->evaluate($reflection, $reflector),
            'or' => $this->left->evaluate($reflection, $reflector) or $this->right->evaluate($reflection, $reflector),
            'xor' => $this->left->evaluate($reflection, $reflector) xor $this->right->evaluate($reflection, $reflector),
            '-' => $this->left->evaluate($reflection, $reflector) - $this->right->evaluate($reflection, $reflector),
            '%' => $this->left->evaluate($reflection, $reflector) % $this->right->evaluate($reflection, $reflector),
            '*' => $this->left->evaluate($reflection, $reflector) * $this->right->evaluate($reflection, $reflector),
            '!=' => $this->left->evaluate($reflection, $reflector) != $this->right->evaluate($reflection, $reflector),
            '!==' => $this->left->evaluate($reflection, $reflector) !== $this->right->evaluate($reflection, $reflector),
            '+' => $this->left->evaluate($reflection, $reflector) + $this->right->evaluate($reflection, $reflector),
            '**' => $this->left->evaluate($reflection, $reflector) ** $this->right->evaluate($reflection, $reflector),
            '<<' => $this->left->evaluate($reflection, $reflector) << $this->right->evaluate($reflection, $reflector),
            '>>' => $this->left->evaluate($reflection, $reflector) >> $this->right->evaluate($reflection, $reflector),
            '<' => $this->left->evaluate($reflection, $reflector) < $this->right->evaluate($reflection, $reflector),
            '<=' => $this->left->evaluate($reflection, $reflector) <= $this->right->evaluate($reflection, $reflector),
            '<=>' => $this->left->evaluate($reflection, $reflector) <=> $this->right->evaluate($reflection, $reflector),
        };
    }
}
