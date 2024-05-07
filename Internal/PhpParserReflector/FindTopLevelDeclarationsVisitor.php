<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParserReflector;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeVisitorAbstract;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\DeclarationIdMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class FindTopLevelDeclarationsVisitor extends NodeVisitorAbstract
{
    /**
     * @psalm-readonly-allow-private-mutation
     * @var DeclarationIdMap<ClassId, ClassLike>
     */
    public DeclarationIdMap $nodes;

    public function __construct()
    {
        /** @var DeclarationIdMap<ClassId, ClassLike> */
        $this->nodes = new DeclarationIdMap();
    }

    public function beforeTraverse(array $nodes): ?array
    {
        /** @var DeclarationIdMap<ClassId, ClassLike> */
        $this->nodes = new DeclarationIdMap();

        return null;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof ClassLike && $node->name !== null) {
            $id = SetTypeContextVisitor::getNodeTypeContext($node)->id;
            \assert($id instanceof ClassId);
            $this->nodes = $this->nodes->with($id, $node);

            return null;
        }

        return null;
    }
}
