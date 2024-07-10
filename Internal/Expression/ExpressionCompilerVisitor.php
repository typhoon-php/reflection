<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ExpressionCompilerVisitor extends NodeVisitorAbstract implements ExpressionCompilerProvider
{
    private string $namespace = '';

    private string $function = '';

    private string $class = '';

    private ?string $parentClass = null;

    private string $trait = '';

    private string $method = '';

    public function __construct(
        private readonly string $file = '',
    ) {}

    public function get(): ExpressionCompiler
    {
        // todo optimize via memoization
        return new ExpressionCompiler(
            file: $this->file,
            namespace: $this->namespace,
            function: $this->function,
            class: $this->class,
            parentClass: $this->parentClass,
            trait: $this->trait,
            method: $this->method,
        );
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Namespace_) {
            $this->namespace = $node->name?->toString() ?? '';

            return null;
        }

        if ($node instanceof Function_) {
            \assert($node->namespacedName !== null);
            $this->function = $node->namespacedName->toString();

            return null;
        }

        if ($node instanceof ClassLike) {
            if ($node->name === null) {
                // todo
                return null;
            }

            \assert($node->namespacedName !== null);
            $this->class = $node->namespacedName->toString();

            if ($node instanceof Trait_) {
                $this->trait = $this->class;
            }

            if ($node instanceof Class_) {
                $this->parentClass = $node->extends?->toString();
            }

            return null;
        }

        if ($node instanceof ClassMethod) {
            $this->method = $node->name->name;

            return null;
        }

        return null;
    }

    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof Namespace_) {
            $this->namespace = '';

            return null;
        }

        if ($node instanceof Function_) {
            $this->function = '';

            return null;
        }

        if ($node instanceof ClassLike) {
            $this->class = '';
            $this->parentClass = null;
            $this->trait = '';

            return null;
        }

        if ($node instanceof ClassMethod) {
            $this->method = '';

            return null;
        }

        return null;
    }
}
