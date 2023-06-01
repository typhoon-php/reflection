<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeReflector;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike as ClassLikeNode;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection
 */
final class FindClassLikeNodeVisitor extends NodeVisitorAbstract
{
    /**
     * @psalm-readonly-allow-private-mutation
     */
    public ?ClassLikeNode $node = null;

    /**
     * @param class-string $class
     */
    public function __construct(
        private readonly string $class,
    ) {
    }

    public function beforeTraverse(array $nodes): void
    {
        $this->node = null;
    }

    public function enterNode(Node $node)
    {
        if (!$node instanceof ClassLikeNode) {
            return null;
        }

        if ($node->namespacedName?->toString() === $this->class) {
            $this->node = $node;

            return NodeTraverser::STOP_TRAVERSAL;
        }

        return NodeTraverser::DONT_TRAVERSE_CHILDREN;
    }
}
