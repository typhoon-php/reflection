<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParserReflector;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class FindAnonymousClassVisitor extends NodeVisitorAbstract
{
    /**
     * @psalm-readonly-allow-private-mutation
     */
    public ?Node $node = null;

    /**
     * @param positive-int $line
     */
    public function __construct(
        private readonly int $line,
    ) {}

    public function beforeTraverse(array $nodes): ?array
    {
        $this->node = null;

        return null;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node->getStartLine() < $this->line) {
            return null;
        }

        if (!$node instanceof Class_ || $node->name !== null) {
            return null;
        }

        if ($this->node !== null) {
            if ($node->getStartLine() === $this->line) {
                throw new \LogicException('More than 1 anonymous classes on line');
            }

            return null;
        }

        $this->node = $node;

        return null;
    }
}
