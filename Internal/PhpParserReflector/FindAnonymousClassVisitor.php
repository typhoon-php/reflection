<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParserReflector;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\TypeContext\NodeVisitor\TypeContextProvider;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class FindAnonymousClassVisitor extends NodeVisitorAbstract
{
    public ?TypedMap $data = null;

    public function __construct(
        private readonly TypeContextProvider $typeContextProvider,
        private readonly AnonymousClassId $id,
    ) {}

    public function beforeTraverse(array $nodes): ?array
    {
        $this->data = null;

        return null;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node->getStartLine() < $this->id->line) {
            return null;
        }

        if ($node->getStartLine() > $this->id->line) {
            return NodeTraverser::STOP_TRAVERSAL;
        }

        if (!$node instanceof Class_ || $node->name !== null) {
            return null;
        }

        if ($this->data !== null) {
            throw new \LogicException('More than 1 anonymous classes on line');
        }

        $this->data = (new TypedMap())
            ->with(Data::File(), $this->id->file)
            ->with(Data::Node(), $node)
            ->with(Data::TypeContext(), $this->typeContextProvider->typeContext());

        return null;
    }
}
