<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParser;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeVisitorAbstract;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\Internal\IdMap;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Context\ContextVisitor;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class CollectIdReflectorsVisitor extends NodeVisitorAbstract
{
    /**
     * @psalm-readonly-allow-private-mutation
     * @var IdMap<NamedFunctionId|NamedClassId|AnonymousClassId, \Closure(): TypedMap>
     */
    public IdMap $idReflectors;

    public function __construct(
        private readonly NodeReflector $nodeReflector,
    ) {
        /** @var IdMap<NamedFunctionId|NamedClassId|AnonymousClassId, \Closure(): TypedMap> */
        $this->idReflectors = new IdMap();
    }

    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof Function_) {
            $id = ContextVisitor::fromNode($node)->currentId;
            \assert($id instanceof NamedFunctionId);

            $nodeReflector = $this->nodeReflector;
            $this->idReflectors = $this->idReflectors->with($id, static fn(): TypedMap => $nodeReflector->reflectFunction($node));

            return null;
        }

        if ($node instanceof ClassLike) {
            $id = ContextVisitor::fromNode($node)->currentId;
            \assert($id instanceof NamedClassId || $id instanceof AnonymousClassId);

            $nodeReflector = $this->nodeReflector;
            $this->idReflectors = $this->idReflectors->with($id, static fn(): TypedMap => $nodeReflector->reflectClassLike($node));

            return null;
        }

        return null;
    }
}
