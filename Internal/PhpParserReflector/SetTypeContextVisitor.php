<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParserReflector;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;
use Typhoon\TypeContext\TypeContext;
use Typhoon\TypeContext\TypeContextProvider;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class SetTypeContextVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly TypeContextProvider $typeContextProvider,
    ) {}

    public static function getNodeTypeContext(ClassLike|ClassMethod $node): TypeContext
    {
        $typeContext = $node->getAttribute(TypeContext::class);
        \assert($typeContext instanceof TypeContext);

        return $typeContext;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof ClassLike || $node instanceof ClassMethod) {
            if (!$node->hasAttribute(TypeContext::class)) {
                $node->setAttribute(TypeContext::class, $this->typeContextProvider->typeContext());
            }

            return null;
        }

        return null;
    }
}
