<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParserReflector;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\DataStorage;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\TypeContext\NodeVisitor\TypeContextProvider;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\classId;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class SymbolReflectingVisitor extends NodeVisitorAbstract
{
    private readonly FixNodeStartLineVisitor $fixNodeStartLineVisitor;

    /**
     * @param list<ReflectionHook> $hooks
     */
    public function __construct(
        string $code,
        private readonly TypedMap $data,
        private readonly TypeContextProvider $typeContextProvider,
        private readonly DataStorage $storage,
        private readonly array $hooks = [],
    ) {
        $this->fixNodeStartLineVisitor = new FixNodeStartLineVisitor($code);
    }

    public function enterNode(Node $node): ?int
    {
        if (($node instanceof ClassLike || $node instanceof Function_) && $node->name !== null) {
            $typeContext = $this->typeContextProvider->typeContext();
            $id = classId($typeContext->resolveDeclaredName($node->name)->toStringWithoutSlash());
            $data = $this->data
                ->with(Data::Node(), $node)
                ->with(Data::TypeContext(), $typeContext);
            $this->storage->saveDeferred($id, function () use ($id, $data, $node): TypedMap {
                PhpParser::traverse([$node], [$this->fixNodeStartLineVisitor]);

                foreach ($this->hooks as $hook) {
                    $data = $hook->reflect($id, $data);
                }

                return $data;
            });

            return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
        }

        return null;
    }
}
