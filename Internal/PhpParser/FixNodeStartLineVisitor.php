<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParser;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeVisitorAbstract;
use function Typhoon\Reflection\Internal\array_value_last;

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

    public static function fromCode(string $code): self
    {
        return new self(\PhpToken::tokenize($code));
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Function_) {
            if ($node->attrGroups !== []) {
                $node->setAttribute(self::START_LINE_ATTRIBUTE, $this->findFirstTokenLine(
                    array_value_last($node->attrGroups)->getEndFilePos(),
                    [T_FUNCTION],
                ));
            }

            return null;
        }

        if ($node instanceof ClassLike) {
            if ($node->attrGroups !== []) {
                $node->setAttribute(self::START_LINE_ATTRIBUTE, $this->findFirstTokenLine(
                    array_value_last($node->attrGroups)->getEndFilePos(),
                    [T_FINAL, T_READONLY, T_ABSTRACT, T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM],
                ));
            }

            return null;
        }

        if ($node instanceof ClassMethod) {
            if ($node->attrGroups !== []) {
                $node->setAttribute(self::START_LINE_ATTRIBUTE, $this->findFirstTokenLine(
                    array_value_last($node->attrGroups)->getEndFilePos(),
                    [T_FINAL, T_ABSTRACT, T_STATIC, T_FUNCTION],
                ));
            }

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
