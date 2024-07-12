<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ConstantConstantExpressionCompilerVisitor extends NodeVisitorAbstract implements ConstantExpressionCompilerProvider
{
    /**
     * @var list<ConstantExpressionCompiler>
     */
    private array $compilers = [];

    /**
     * @param ?non-empty-string $file
     */
    public function __construct(
        private readonly ?string $file = null,
    ) {}

    public function get(): ConstantExpressionCompiler
    {
        $lastKey = array_key_last($this->compilers);

        if ($lastKey === null) {
            throw new \LogicException('This must never happen if the method is called during traversal');
        }

        return $this->compilers[$lastKey];
    }

    public function beforeTraverse(array $nodes): ?array
    {
        $this->compilers = [new ConstantExpressionCompiler($this->file)];

        return null;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Namespace_) {
            $this->compilers[] = $this->get()->atNamespace($node->name?->toString());

            return null;
        }

        if ($node instanceof Function_) {
            if ($node->namespacedName === null) {
                throw new \LogicException(sprintf('Name resolution via %s is required for %s', NameResolver::class, self::class));
            }

            $this->compilers[] = $this->get()->atFunction($node->namespacedName->toString());

            return null;
        }

        if ($node instanceof ArrowFunction || $node instanceof Closure) {
            $this->compilers[] = $this->get()->atAnonymousFunction();

            return null;
        }

        if ($node instanceof ClassLike) {
            if ($node->name === null) {
                \assert($node instanceof Class_, 'Null name is only possible in anonymous classes');
                $this->compilers[] = $this->get()->atAnonymousClass($node->extends?->toString());

                return null;
            }

            if ($node->namespacedName === null) {
                throw new \LogicException(sprintf('Name resolution via %s is required for %s', NameResolver::class, self::class));
            }

            $this->compilers[] = $this->get()->atClass(
                class: $node->namespacedName->toString(),
                parent: $node instanceof Class_ ? $node->extends?->toString() : null,
                trait: $node instanceof Trait_,
            );

            return null;
        }

        if ($node instanceof ClassMethod) {
            $this->compilers[] = $this->get()->atMethod($node->name->name);

            return null;
        }

        return null;
    }

    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof Namespace_ || $node instanceof FunctionLike || $node instanceof ClassLike) {
            array_pop($this->compilers);

            return null;
        }

        return null;
    }
}
