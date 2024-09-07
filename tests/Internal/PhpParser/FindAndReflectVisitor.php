<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeVisitorAbstract;
use Typhoon\Reflection\Internal\Context\ContextProvider;
use Typhoon\Type\Type;

final class FindAndReflectVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<?Type>
     */
    public array $types = [];

    /**
     * @param \Closure(Node): \Generator<array-key, Expr> $expressionFinder
     */
    public function __construct(
        private readonly ContextProvider $contextProvider,
        private readonly \Closure $expressionFinder,
    ) {}

    public function leaveNode(Node $node): ?int
    {
        $reflector = new ConstantExpressionTypeReflector($this->contextProvider->get());

        foreach (($this->expressionFinder)($node) as $key => $expr) {
            $this->types[$key] = $reflector->reflect($expr);
        }

        return null;
    }
}
