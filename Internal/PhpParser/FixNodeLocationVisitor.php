<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParser;

use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeVisitorAbstract;
use function Typhoon\Reflection\Internal\array_value_last;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class FixNodeLocationVisitor extends NodeVisitorAbstract
{
    /**
     * @param list<\PhpToken> $tokens
     */
    public function __construct(
        private readonly array $tokens,
    ) {}

    public static function fromCode(string $code): self
    {
        return new self(\PhpToken::tokenize($code));
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Function_) {
            $this->fix($node, $node->attrGroups, [T_FUNCTION]);

            return null;
        }

        if ($node instanceof ClassLike) {
            $this->fix($node, $node->attrGroups, [T_FINAL, T_READONLY, T_ABSTRACT, T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM]);

            return null;
        }

        if ($node instanceof ClassMethod) {
            $this->fix($node, $node->attrGroups, [T_FINAL, T_ABSTRACT, T_STATIC, T_FUNCTION, T_PUBLIC, T_PROTECTED, T_PRIVATE]);

            return null;
        }

        return null;
    }

    /**
     * @param array<AttributeGroup> $attrGroups
     * @param non-empty-list<int> $tokenKinds
     */
    private function fix(Node $node, array $attrGroups, array $tokenKinds): void
    {
        if ($attrGroups === []) {
            return;
        }

        [$index, $token] = $this->findToken(array_value_last($attrGroups)->getEndFilePos(), $tokenKinds);

        $node->setAttribute('startLine', $token->line);
        $node->setAttribute('startFilePos', $token->pos);
        $node->setAttribute('startTokenPos', $index);
    }

    /**
     * @param non-empty-list<int> $kinds
     * @return array{non-negative-int, \PhpToken}
     */
    private function findToken(int $offset, array $kinds): array
    {
        foreach ($this->tokens as $index => $token) {
            if ($token->pos >= $offset && $token->is($kinds)) {
                return [$index, $token];
            }
        }

        throw new \LogicException();
    }
}
