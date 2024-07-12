<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CodeReflector;

use PhpParser\Node;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\FunctionLike;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class IsGeneratorChecker extends NodeVisitorAbstract
{
    private bool $isGenerator = false;

    private bool $enteredFirstFunction = false;

    private function __construct() {}

    public static function check(Node $node): bool
    {
        $visitor = new self();
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse([$node]);

        return $visitor->isGenerator;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof FunctionLike) {
            if ($this->enteredFirstFunction) {
                return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }

            $this->enteredFirstFunction = true;

            return null;
        }

        if ($node instanceof Yield_) {
            $this->isGenerator = true;

            return NodeTraverser::STOP_TRAVERSAL;
        }

        return null;
    }
}
