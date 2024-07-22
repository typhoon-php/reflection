<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Context;

use PhpParser\NameContext;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;
use function Typhoon\Reflection\Internal\array_value_last;
use function Typhoon\Reflection\Internal\column;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ContextVisitor extends NodeVisitorAbstract implements ContextProvider
{
    private readonly Context $fileContext;

    /**
     * @var list<Context>
     */
    private array $declarationContextStack = [];

    /**
     * @param ?non-empty-string $file
     */
    public function __construct(
        ?string $file,
        private readonly string $code,
        private readonly NameContext $nameContext,
        private readonly AnnotatedTypesDriver $annotatedTypesDriver = NullAnnotatedTypesDriver::Instance,
    ) {
        $this->fileContext = Context::start($file);
    }

    public function get(): Context
    {
        return array_value_last($this->declarationContextStack)
            ?? $this->fileContext->withNameContext($this->nameContext);
    }

    public function beforeTraverse(array $nodes): ?array
    {
        $this->declarationContextStack = [];

        return null;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Function_) {
            \assert($node->namespacedName !== null);

            $this->declarationContextStack[] = $this->get()->enterFunction(
                name: $node->namespacedName->toString(),
                templateNames: $this->annotatedTypesDriver->reflectAnnotatedTypeNames($node)->templateNames,
            );

            return null;
        }

        if ($node instanceof Closure || $node instanceof ArrowFunction) {
            $line = $node->getStartLine();
            \assert($line > 0);
            $offset = $node->getStartFilePos();
            \assert($offset >= 0);

            $this->declarationContextStack[] = $this->get()->enterAnonymousFunction(
                line: $line,
                column: column($this->code, $offset),
                templateNames: $this->annotatedTypesDriver->reflectAnnotatedTypeNames($node)->templateNames,
            );

            return null;
        }

        if ($node instanceof Class_) {
            $typeNames = $this->annotatedTypesDriver->reflectAnnotatedTypeNames($node);

            if ($node->name === null) {
                $line = $node->getStartLine();
                \assert($line > 0);
                $offset = $node->getStartFilePos();
                \assert($offset >= 0);

                $this->declarationContextStack[] = $this->get()->enterAnonymousClass(
                    line: $line,
                    column: column($this->code, $offset),
                    parentName: $node->extends?->toString(),
                    aliasNames: $typeNames->aliasNames,
                    templateNames: $typeNames->templateNames,
                );

                return null;
            }

            \assert($node->namespacedName !== null);

            $this->declarationContextStack[] = $this->get()->enterClass(
                name: $node->namespacedName->toString(),
                parentName: $node->extends?->toString(),
                aliasNames: $typeNames->aliasNames,
                templateNames: $typeNames->templateNames,
            );

            return null;
        }

        if ($node instanceof Interface_) {
            \assert($node->namespacedName !== null);
            $typeNames = $this->annotatedTypesDriver->reflectAnnotatedTypeNames($node);

            $this->declarationContextStack[] = $this->get()->enterInterface(
                name: $node->namespacedName->toString(),
                aliasNames: $typeNames->aliasNames,
                templateNames: $typeNames->templateNames,
            );

            return null;
        }

        if ($node instanceof Trait_) {
            \assert($node->namespacedName !== null);
            $typeNames = $this->annotatedTypesDriver->reflectAnnotatedTypeNames($node);

            $this->declarationContextStack[] = $this->get()->enterTrait(
                name: $node->namespacedName->toString(),
                aliasNames: $typeNames->aliasNames,
                templateNames: $typeNames->templateNames,
            );

            return null;
        }

        if ($node instanceof Enum_) {
            \assert($node->namespacedName !== null);
            $typeNames = $this->annotatedTypesDriver->reflectAnnotatedTypeNames($node);

            $this->declarationContextStack[] = $this->get()->enterEnum(
                name: $node->namespacedName->toString(),
                aliasNames: $typeNames->aliasNames,
                templateNames: $typeNames->templateNames,
            );

            return null;
        }

        if ($node instanceof ClassMethod) {
            $typeNames = $this->annotatedTypesDriver->reflectAnnotatedTypeNames($node);

            $this->declarationContextStack[] = $this->get()->enterMethod(
                name: $node->name->name,
                templateNames: $typeNames->templateNames,
            );

            return null;
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof FunctionLike || $node instanceof ClassLike) {
            array_pop($this->declarationContextStack);

            return null;
        }

        return null;
    }
}
