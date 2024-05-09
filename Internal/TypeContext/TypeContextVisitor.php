<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\TypeContext;

use PhpParser\NameContext;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\DeclarationId;
use function Typhoon\DeclarationId\anonymousClassId;
use function Typhoon\DeclarationId\classConstantId;
use function Typhoon\DeclarationId\classId;
use function Typhoon\DeclarationId\methodId;
use function Typhoon\DeclarationId\propertyId;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class TypeContextVisitor extends NodeVisitorAbstract implements TypeContextProvider
{
    /**
     * @var list<DeclarationId>
     */
    private array $idsStack = [];

    /**
     * @var list<ClassId|AnonymousClassId>
     */
    private array $selfStack = [];

    /**
     * @var list<?ClassId>
     */
    private array $parentStack = [];

    /**
     * @var list<bool>
     */
    private array $traitStack = [];

    /**
     * @var list<non-empty-string>
     */
    private array $aliasNames = [];

    /**
     * @var list<list<non-empty-string>>
     */
    private array $templateNamesStack = [];

    private ?TypeContext $typeContext = null;

    /**
     * @param ?non-empty-string $file
     */
    public function __construct(
        private readonly NameContext $nameContext,
        private readonly AnnotatedTypesDriver $reader = new NullAnnotatedTypesDriver(),
        private readonly ?string $file = null,
    ) {}

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof ClassLike) {
            $this->typeContext = null;

            $typeDeclarations = $this->reader->reflectTypeDeclarations($node);

            if ($node->name === null) {
                if ($this->file === null) {
                    throw new \LogicException('Anonymous class file is null');
                }

                $classId = anonymousClassId($this->file, $node->getStartLine());
            } else {
                \assert($node->namespacedName !== null);
                $classId = classId($node->namespacedName->toString());
                $this->aliasNames = $typeDeclarations->aliasNames;
            }

            $this->idsStack[] = $classId;
            $this->selfStack[] = $classId;
            $this->parentStack[] = $this->resolveParent($node);
            $this->traitStack[] = $node instanceof Trait_;
            $this->templateNamesStack[] = $typeDeclarations->templateNames;

            return null;
        }

        if ($node instanceof Const_) {
            $this->typeContext = null;

            $self = end($this->selfStack);
            \assert($self !== false);

            $this->idsStack[] = classConstantId($self, $node->name->name);

            return null;
        }

        if ($node instanceof PropertyItem) {
            $this->typeContext = null;

            $self = end($this->selfStack);
            \assert($self !== false);

            $this->idsStack[] = propertyId($self, $node->name->name);

            return null;
        }

        if ($node instanceof ClassMethod) {
            $this->typeContext = null;

            $self = end($this->selfStack);
            \assert($self !== false);

            $templateNames = end($this->templateNamesStack);
            \assert($templateNames !== false);

            $methodId = methodId($self, $node->name->name);
            $this->idsStack[] = $methodId;
            $this->templateNamesStack[] = [
                ...$templateNames,
                ...$this->reader->reflectTypeDeclarations($node)->templateNames,
            ];

            return null;
        }

        return null;
    }

    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof ClassLike) {
            $this->typeContext = null;

            array_pop($this->idsStack);
            array_pop($this->selfStack);
            array_pop($this->parentStack);
            array_pop($this->traitStack);
            array_pop($this->templateNamesStack);

            if ($node->name !== null) {
                $this->aliasNames = [];
            }

            return null;
        }

        if ($node instanceof PropertyItem) {
            $this->typeContext = null;

            array_pop($this->idsStack);

            return null;
        }

        if ($node instanceof ClassMethod) {
            $this->typeContext = null;

            array_pop($this->idsStack);
            array_pop($this->templateNamesStack);

            return null;
        }

        return null;
    }

    public function typeContext(): TypeContext
    {
        return $this->typeContext ??= new TypeContext(
            nameContext: $this->nameContext,
            id: end($this->idsStack) ?: null,
            self: end($this->selfStack) ?: null,
            parent: end($this->parentStack) ?: null,
            aliasNames: $this->aliasNames,
            templateNames: $this->templateNamesStack === [] ? [] : end($this->templateNamesStack),
        );
    }

    private function resolveParent(ClassLike $node): ?ClassId
    {
        if (!$node instanceof Class_ || $node->extends === null) {
            return null;
        }

        $classId = classId($node->extends->toString());
        \assert($classId instanceof ClassId);

        return $classId;
    }
}
