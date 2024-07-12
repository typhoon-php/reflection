<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CodeReflector;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class FixNodeStartLineVisitor extends NodeVisitorAbstract
{
    private const START_LINE_ATTRIBUTE = 'startLine';

    /**
     * @param array<\PhpToken> $tokens
     */
    public function __construct(
        private readonly array $tokens,
    ) {}

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\ClassLike && $node->attrGroups !== []) {
            $node->setAttribute(self::START_LINE_ATTRIBUTE, $this->findFirstTokenLine(
                end($node->attrGroups)->getEndFilePos(),
                [T_FINAL, T_READONLY, T_ABSTRACT, T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM],
            ));

            return null;
        }

        if ($node instanceof Node\Stmt\ClassMethod && $node->attrGroups !== []) {
            $node->setAttribute(self::START_LINE_ATTRIBUTE, $this->findFirstTokenLine(
                end($node->attrGroups)->getEndFilePos(),
                [T_FINAL, T_ABSTRACT, T_STATIC, T_FUNCTION],
            ));

            return null;
        }

        return null;
    }

    /**
     * @param non-empty-list<int> $tokenKinds
     */
    private function findFirstTokenLine(int $offset, array $tokenKinds): int
    {
        foreach ($this->tokens as $token) {
            if ($token->pos >= $offset && $token->is($tokenKinds)) {
                return $token->line;
            }
        }

        return -1;
    }
}
