<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\TypeContext;

use PhpParser\NameContext;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;
use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassConstantId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\TemplateId;
use function Typhoon\DeclarationId\aliasId;
use function Typhoon\DeclarationId\anonymousClassId;
use function Typhoon\DeclarationId\classConstantId;
use function Typhoon\DeclarationId\classId;
use function Typhoon\DeclarationId\methodId;
use function Typhoon\DeclarationId\propertyId;
use function Typhoon\DeclarationId\templateId;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class TypeContextVisitor extends NodeVisitorAbstract implements TypeContextProvider
{
    /**
     * @var list<TypeContext>
     */
    private array $contextStack = [];

    /**
     * @param ?non-empty-string $file
     */
    public function __construct(
        private readonly NameContext $nameContext,
        private readonly AnnotatedTypesDriver $reader = new NullAnnotatedTypesDriver(),
        private readonly ?string $file = null,
    ) {}

    public function beforeTraverse(array $nodes): ?array
    {
        $this->contextStack = [];

        return null;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof ClassLike) {
            $this->contextStack[] = $this->buildClassContext($node);

            return null;
        }

        if ($node instanceof Const_) {
            $typeContext = $this->typeContext();

            if ($typeContext->id instanceof ClassId || $typeContext->id instanceof AnonymousClassId) {
                $this->contextStack[] = new TypeContext(
                    nameContext: $this->nameContext,
                    id: classConstantId($typeContext->id, $node->name->name),
                    self: $typeContext->self,
                    parent: $typeContext->parent,
                    aliases: $typeContext->aliases,
                    templates: $typeContext->templates,
                );

                return null;
            }

            return null;
        }

        if ($node instanceof PropertyProperty) {
            $typeContext = $this->typeContext();
            \assert($typeContext->id instanceof ClassId || $typeContext->id instanceof AnonymousClassId);

            $this->contextStack[] = new TypeContext(
                nameContext: $this->nameContext,
                id: propertyId($typeContext->id, $node->name->name),
                self: $typeContext->self,
                parent: $typeContext->parent,
                aliases: $typeContext->aliases,
                templates: $typeContext->templates,
            );

            return null;
        }

        if ($node instanceof ClassMethod) {
            $typeContext = $this->typeContext();
            \assert($typeContext->id instanceof ClassId || $typeContext->id instanceof AnonymousClassId);
            $typeDeclarations = $this->reader->reflectTypeDeclarations($node);
            $methodId = methodId($typeContext->id, $node->name->name);

            $this->contextStack[] = new TypeContext(
                nameContext: $this->nameContext,
                id: $methodId,
                self: $typeContext->self,
                parent: $typeContext->parent,
                aliases: $typeContext->aliases,
                templates: [
                    ...$typeContext->templates,
                    ...array_map(
                        static fn(string $name): TemplateId => templateId($methodId, $name),
                        $typeDeclarations->templateNames,
                    ),
                ],
            );

            return null;
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassLike || $node instanceof PropertyProperty || $node instanceof ClassMethod) {
            array_pop($this->contextStack);

            return null;
        }

        if ($node instanceof Const_) {
            $typeContext = $this->typeContext();

            if ($typeContext->id instanceof ClassConstantId
                || $typeContext->id instanceof ClassId
                || $typeContext->id instanceof AnonymousClassId
            ) {
                array_pop($this->contextStack);

                return null;
            }

            return null;
        }

        return null;
    }

    public function typeContext(): TypeContext
    {
        return end($this->contextStack) ?: new TypeContext($this->nameContext);
    }

    private function buildClassContext(ClassLike $node): TypeContext
    {
        $typeDeclarations = $this->reader->reflectTypeDeclarations($node);

        if ($node->name === null) {
            \assert($node instanceof Class_);

            if ($this->file === null) {
                throw new \LogicException('No file for anonymous class');
            }

            $classId = anonymousClassId($this->file, $node->getStartLine());

            return new TypeContext(
                nameContext: $this->nameContext,
                id: $classId,
                self: $classId,
                parent: $node->extends === null ? null : classId($node->extends->toString()),
                templates: array_map(
                    static fn(string $name): TemplateId => templateId($classId, $name),
                    $typeDeclarations->templateNames,
                ),
            );
        }

        \assert($node->namespacedName !== null);
        $classId = classId($node->namespacedName->toString());
        $aliases = array_map(
            static fn(string $name): AliasId => aliasId($classId, $name),
            $typeDeclarations->aliasNames,
        );
        $templates = array_map(
            static fn(string $name): TemplateId => templateId($classId, $name),
            $typeDeclarations->templateNames,
        );

        if ($node instanceof Interface_ || $node instanceof Enum_) {
            return new TypeContext(
                nameContext: $this->nameContext,
                id: $classId,
                self: $classId,
                aliases: $aliases,
                templates: $templates,
            );
        }

        if ($node instanceof Class_) {
            return new TypeContext(
                nameContext: $this->nameContext,
                id: $classId,
                self: $classId,
                parent: $node->extends === null ? null : classId($node->extends->toString()),
                aliases: $aliases,
                templates: $templates,
            );
        }

        \assert($node instanceof Trait_);

        return new TypeContext(
            nameContext: $this->nameContext,
            id: $classId,
            aliases: $aliases,
            templates: $templates,
        );
    }
}
