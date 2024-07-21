<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Context;

use PhpParser\NameContext;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;
use function Typhoon\Reflection\Internal\array_value_last;

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
     * @var ?non-negative-int
     */
    private ?int $codeLength = null;

    /**
     * @param ?non-empty-string $file
     */
    public function __construct(
        private readonly NameContext $nameContext,
        private readonly AnnotatedTypesDriver $annotatedTypesDriver,
        private readonly string $code,
        ?string $file = null,
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

        if ($node instanceof Class_) {
            $typeNames = $this->annotatedTypesDriver->reflectAnnotatedTypeNames($node);

            if ($node->name === null) {
                $startLine = $node->getStartLine();
                \assert($startLine > 0);

                $this->declarationContextStack[] = $this->get()->enterAnonymousClass(
                    line: $startLine,
                    column: $this->column($node),
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
        if ($node instanceof Function_
         || $node instanceof Class_
         || $node instanceof Interface_
         || $node instanceof Trait_
         || $node instanceof Enum_
         || $node instanceof ClassMethod
        ) {
            array_pop($this->declarationContextStack);

            return null;
        }

        return null;
    }

    /**
     * @return positive-int
     */
    private function column(Node $node): int
    {
        $this->codeLength ??= \strlen($this->code);
        $startFilePosition = $node->getStartFilePos();
        \assert($startFilePosition >= 0);

        $lineStartPosition = strrpos($this->code, "\n", $startFilePosition - $this->codeLength);

        if ($lineStartPosition === false) {
            $lineStartPosition = -1;
        }

        $column = $startFilePosition - $lineStartPosition;
        \assert($column > 0);

        return $column;
    }
}
