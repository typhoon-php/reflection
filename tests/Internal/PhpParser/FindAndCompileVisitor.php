<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeVisitorAbstract;
use Typhoon\Reflection\Internal\ConstantExpression\Expression;
use Typhoon\Reflection\Internal\Context\ContextProvider;

final class FindAndCompileVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<Expression>
     */
    public array $expressions = [];

    /**
     * @param \Closure(Node): \Generator<array-key, Expr> $expressionFinder
     */
    public function __construct(
        private readonly ContextProvider $contextProvider,
        private readonly \Closure $expressionFinder,
    ) {}

    public function leaveNode(Node $node): ?int
    {
        $compiler = new ConstantExpressionCompiler($this->contextProvider->get());

        foreach (($this->expressionFinder)($node) as $key => $expr) {
            $this->expressions[$key] = $compiler->compile($expr);
        }

        return null;
    }
}
