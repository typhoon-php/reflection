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
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use function Typhoon\Reflection\Internal\array_value_last;
use function Typhoon\Reflection\Internal\column;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ContextVisitor extends NodeVisitorAbstract implements ContextProvider
{
    private readonly Context $fileContext;

    private readonly string $code;

    /**
     * @var list<Context>
     */
    private array $declarationContextStack = [];

    public function __construct(
        private readonly NameContext $nameContext,
        private readonly AnnotatedTypesDriver $annotatedTypesDriver,
        TypedMap $resourceData,
    ) {
        $this->fileContext = Context::start($resourceData[Data::File]);
        $this->code = $resourceData[Data::Code];
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
                $startPosition = $node->getStartFilePos();
                \assert($startPosition >= 0);
                $startLine = $node->getStartLine();
                \assert($startLine > 0);

                $this->declarationContextStack[] = $this->get()->enterAnonymousClass(
                    line: $startLine,
                    column: column($this->code, $startPosition),
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
}
