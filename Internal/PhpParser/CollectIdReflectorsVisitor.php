<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParser;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeVisitorAbstract;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\Internal\IdMap;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Context\ContextProvider;
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
        private readonly ContextProvider $contextProvider,
    ) {
        /** @var IdMap<NamedFunctionId|NamedClassId|AnonymousClassId, \Closure(): TypedMap> */
        $this->idReflectors = new IdMap();
    }

    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof Function_) {
            $context = $this->contextProvider->current();
            \assert($context->currentId instanceof NamedFunctionId);

            $nodeReflector = $this->nodeReflector;
            $this->idReflectors = $this->idReflectors->with(
                $context->currentId,
                static fn(): TypedMap => $nodeReflector->reflectFunction($node, $context),
            );

            return null;
        }

        if ($node instanceof ClassLike) {
            $context = $this->contextProvider->current();
            \assert($context->currentId instanceof NamedClassId || $context->currentId instanceof AnonymousClassId);

            $nodeReflector = $this->nodeReflector;
            $this->idReflectors = $this->idReflectors->with(
                $context->currentId,
                static fn(): TypedMap => $nodeReflector->reflectClassLike($node, $context),
            );

            return null;
        }

        if ($node instanceof ClassMethod) {
            NodeContextAttribute::set($node, $this->contextProvider->current());

            return null;
        }

        return null;
    }
}
