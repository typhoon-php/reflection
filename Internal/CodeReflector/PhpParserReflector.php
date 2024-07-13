<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CodeReflector;

use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\EnumCase;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\TraitUseAdaptation\Alias;
use PhpParser\Node\Stmt\TraitUseAdaptation\Precedence;
use PhpParser\Node\UnionType;
use PhpParser\NodeVisitorAbstract;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\ClassKind;
use Typhoon\Reflection\Internal\ConstantExpression\ConstantExpressionCompilerProvider;
use Typhoon\Reflection\Internal\ConstantExpression\Expression;
use Typhoon\Reflection\Internal\ConstantExpression\Values;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\Data\TraitMethodAlias;
use Typhoon\Reflection\Internal\Data\TypeData;
use Typhoon\Reflection\Internal\Data\Visibility;
use Typhoon\Reflection\Internal\DeclarationId\IdMap;
use Typhoon\Reflection\Internal\NativeAdapter\NativeTraitInfo;
use Typhoon\Reflection\Internal\NativeAdapter\NativeTraitInfoKey;
use Typhoon\Reflection\Internal\TypeContext\TypeContext;
use Typhoon\Reflection\Internal\TypeContext\TypeContextProvider;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PhpParserReflector extends NodeVisitorAbstract
{
    /**
     * @psalm-readonly-allow-private-mutation
     * @var IdMap<NamedClassId|AnonymousClassId, TypedMap>
     */
    public IdMap $reflected;

    public function __construct(
        private readonly TypeContextProvider $typeContextProvider,
        private readonly ConstantExpressionCompilerProvider $constantExpressionCompilerProvider,
        private readonly TypedMap $baseData = new TypedMap(),
    ) {
        /** @var IdMap<NamedClassId|AnonymousClassId, TypedMap> */
        $this->reflected = new IdMap();
    }

    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof ClassLike) {
            $typeContext = $this->typeContextProvider->get();
            $id = $typeContext->id;
            \assert($id instanceof NamedClassId || $id instanceof AnonymousClassId);

            $data = $this->reflectClass($node, $typeContext);
            $this->reflected = $this->reflected->with($id, $data);

            // TODO: check 1 one line
            if ($id instanceof AnonymousClassId) {
                $this->reflected = $this->reflected->with(Id::anonymousClass($id->file, $id->line), $data);
            }

            return null;
        }

        return null;
    }

    private function reflectClass(ClassLike $node, TypeContext $typeContext): TypedMap
    {
        $data = $this->baseData
            ->merge($this->reflectNode($node))
            ->set(Data::TypeContext, $typeContext)
            ->set(Data::Attributes, $this->reflectAttributes($node->attrGroups));

        if ($node instanceof Class_) {
            return $data
                ->set(Data::ClassKind, ClassKind::Class_)
                ->set(Data::UnresolvedParent, $node->extends === null ? null : [$node->extends->toString(), []])
                ->set(Data::UnresolvedInterfaces, $this->reflectInterfaces($node->implements))
                ->merge($this->reflectTraitUses($node->getTraitUses()))
                ->set(Data::Abstract, $node->isAbstract())
                ->set(Data::NativeReadonly, $node->isReadonly())
                ->set(Data::NativeFinal, $node->isFinal())
                ->set(Data::ClassConstants, $this->reflectConstants($typeContext, $node->getConstants()))
                ->set(Data::Properties, $this->reflectProperties($typeContext, $node->getProperties()))
                ->set(Data::Methods, $this->reflectMethods($node->getMethods()));
        }

        if ($node instanceof Interface_) {
            return $data
                ->set(Data::ClassKind, ClassKind::Interface)
                ->set(Data::UnresolvedInterfaces, $this->reflectInterfaces($node->extends))
                ->set(Data::ClassConstants, $this->reflectConstants($typeContext, $node->getConstants()))
                ->set(Data::Methods, $this->reflectMethods($node->getMethods()));
        }

        if ($node instanceof Enum_) {
            $scalarType = $this->reflectType($typeContext, $node->scalarType);

            return $data
                ->set(Data::ClassKind, ClassKind::Enum)
                ->set(Data::UnresolvedInterfaces, $this->reflectInterfaces($node->implements))
                ->merge($this->reflectTraitUses($node->getTraitUses()))
                ->set(Data::NativeFinal, true)
                ->set(Data::EnumScalarType, $scalarType)
                ->set(Data::ClassConstants, [
                    ...$this->reflectConstants($typeContext, $node->getConstants()),
                    ...$this->reflectEnumCases($typeContext, array_filter(
                        $node->stmts,
                        static fn(Node $node): bool => $node instanceof EnumCase,
                    )),
                ])
                ->set(Data::Methods, $this->reflectMethods($node->getMethods()));
        }

        if ($node instanceof Trait_) {
            return $data
                ->set(Data::ClassKind, ClassKind::Trait)
                ->merge($this->reflectTraitUses($node->getTraitUses()))
                ->set(Data::ClassConstants, $this->reflectConstants($typeContext, $node->getConstants()))
                ->set(Data::Properties, $this->reflectProperties($typeContext, $node->getProperties()))
                ->set(Data::Methods, $this->reflectMethods($node->getMethods()));
        }

        return $data;
    }

    private function reflectNode(Node $node): TypedMap
    {
        $data = new TypedMap();

        if ($node->getStartLine() > 0) {
            $data = $data->set(Data::StartLine, $node->getStartLine());

            if ($node->getEndLine() > 0) {
                $data = $data->set(Data::EndLine, $node->getEndLine());
            }
        }

        $docComment = $node->getDocComment();

        if ($docComment !== null && $docComment->getText() !== '') {
            $data = $data->set(Data::PhpDoc, $docComment->getText());

            if ($docComment->getStartLine() > 0) {
                $data = $data->set(Data::PhpDocStartLine, $docComment->getStartLine());
            }
        }

        return $data;
    }

    /**
     * @param array<Name> $names
     * @return array<non-empty-string, list<Type>>
     */
    private function reflectInterfaces(array $names): array
    {
        $interfaces = [];

        foreach ($names as $name) {
            $interfaces[$name->toString()] = [];
        }

        return $interfaces;
    }

    /**
     * @param array<TraitUse> $nodes
     */
    private function reflectTraitUses(array $nodes): TypedMap
    {
        $allNames = [];
        $traits = [];
        $precedence = [];
        $phpDocs = [];

        foreach ($nodes as $node) {
            $phpDoc = $node->getDocComment()?->getText() ?? '';

            if ($phpDoc !== '') {
                $phpDocs[] = $phpDoc;
            }

            foreach ($node->traits as $name) {
                $traits[$name->toString()] = [];
                $allNames[] = $name->toString();
            }

            foreach ($node->adaptations as $adaptation) {
                if ($adaptation instanceof Precedence) {
                    \assert($adaptation->trait !== null);
                    $precedence[$adaptation->method->name] = $adaptation->trait->toString();
                }
            }
        }

        $aliases = $this->reflectTraitAliases($nodes, array_keys($traits));

        return (new TypedMap())
            ->set(Data::UnresolvedTraits, $traits)
            ->set(Data::TraitMethodPrecedence, $precedence)
            ->set(Data::TraitMethodAliases, $aliases)
            ->set(Data::UsePhpDocs, $phpDocs)
            ->set(NativeTraitInfoKey::Key, new NativeTraitInfo($allNames, $aliases));
    }

    /**
     * @param array<TraitUse> $nodes
     * @param list<non-empty-string> $traits
     * @return list<TraitMethodAlias>
     */
    private function reflectTraitAliases(array $nodes, array $traits): array
    {
        $aliases = [];

        foreach ($nodes as $node) {
            foreach ($node->adaptations as $adaptation) {
                if ($adaptation instanceof Alias) {
                    if ($adaptation->trait === null) {
                        $aliasTraits = $traits;
                    } else {
                        $aliasTraits = [$adaptation->trait->toString()];
                    }

                    foreach ($aliasTraits as $aliasTrait) {
                        $aliases[] = new TraitMethodAlias(
                            trait: $aliasTrait,
                            method: $adaptation->method->name,
                            newName: $adaptation->newName?->name,
                            newVisibility: $adaptation->newModifier === null ? null : $this->reflectVisibility($adaptation->newModifier),
                        );
                    }
                }
            }
        }

        return $aliases;
    }

    /**
     * @param array<AttributeGroup> $attributeGroups
     * @return list<TypedMap>
     */
    private function reflectAttributes(array $attributeGroups): array
    {
        $attributes = [];

        foreach ($attributeGroups as $attributeGroup) {
            foreach ($attributeGroup->attrs as $attr) {
                $attributes[] = $this->reflectNode($attr)
                    ->set(Data::AttributeClassName, $attr->name->toString())
                    ->set(Data::ArgumentExpressions, $this->reflectArguments($attr->args));
            }
        }

        return $attributes;
    }

    /**
     * @param array<Node\Arg> $nodes
     * @return array<Expression>
     */
    private function reflectArguments(array $nodes): array
    {
        $compiler = $this->constantExpressionCompilerProvider->get();
        $arguments = [];

        foreach ($nodes as $node) {
            if ($node->name === null) {
                $arguments[] = $compiler->compile($node->value);
            } else {
                $arguments[$node->name->name] = $compiler->compile($node->value);
            }
        }

        return $arguments;
    }

    /**
     * @param array<ClassConst> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectConstants(TypeContext $typeContext, array $nodes): array
    {
        $compiler = $this->constantExpressionCompilerProvider->get();
        $constants = [];

        foreach ($nodes as $node) {
            $data = $this
                ->reflectNode($node)
                ->set(Data::Attributes, $this->reflectAttributes($node->attrGroups))
                ->set(Data::NativeFinal, $node->isFinal())
                ->set(Data::Type, new TypeData($this->reflectType($typeContext, $node->type)))
                ->set(Data::Visibility, $this->reflectVisibility($node->flags));

            foreach ($node->consts as $const) {
                $constants[$const->name->name] = $data->set(Data::ValueExpression, $compiler->compile($const->value));
            }
        }

        return $constants;
    }

    /**
     * @param array<EnumCase> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectEnumCases(TypeContext $typeContext, array $nodes): array
    {
        $compiler = $this->constantExpressionCompilerProvider->get();
        $cases = [];

        foreach ($nodes as $node) {
            $name = $node->name->name;
            $data = $this
                ->reflectNode($node)
                ->set(Data::Attributes, $this->reflectAttributes($node->attrGroups))
                ->set(Data::NativeFinal, false)
                ->set(Data::EnumCase, true)
                ->set(Data::Type, new TypeData(types::classConstant($typeContext->resolveType(new Name('self')), $name)))
                ->set(Data::Visibility, Visibility::Public);

            if ($node->expr !== null) {
                $data = $data->set(Data::EnumBackingValueExpression, $compiler->compile($node->expr));
            }

            $cases[$name] = $data;
        }

        return $cases;
    }

    /**
     * @param array<Property> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectProperties(TypeContext $typeContext, array $nodes): array
    {
        $compiler = $this->constantExpressionCompilerProvider->get();
        $properties = [];

        foreach ($nodes as $node) {
            $data = $this
                ->reflectNode($node)
                ->set(Data::Attributes, $this->reflectAttributes($node->attrGroups))
                ->set(Data::Static, $node->isStatic())
                ->set(Data::NativeReadonly, $node->isReadonly())
                ->set(Data::Type, new TypeData($this->reflectType($typeContext, $node->type)))
                ->set(Data::Visibility, $this->reflectVisibility($node->flags));

            foreach ($node->props as $prop) {
                $default = $compiler->compile($prop->default);

                if ($default === null && $node->type === null) {
                    $default = Values::Null;
                }

                $properties[$prop->name->name] = $data->set(Data::DefaultValueExpression, $default);
            }
        }

        return $properties;
    }

    /**
     * @param array<ClassMethod> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectMethods(array $nodes): array
    {
        $methods = [];

        foreach ($nodes as $node) {
            $typeContext = $this->typeContextProvider->get();
            $methods[$node->name->name] = $this->reflectNode($node)
                ->set(Data::TypeContext, $typeContext)
                ->set(Data::Static, $node->isStatic())
                ->set(Data::NativeFinal, $node->isFinal())
                ->set(Data::Abstract, $node->isAbstract())
                ->set(Data::Type, new TypeData($this->reflectType($typeContext, $node->returnType)))
                ->set(Data::Visibility, $this->reflectVisibility($node->flags))
                ->set(Data::ByReference, $node->byRef)
                ->set(Data::Generator, IsGeneratorChecker::check($node))
                ->set(Data::Attributes, $this->reflectAttributes($node->attrGroups))
                ->set(Data::Parameters, $this->reflectParameters($typeContext, $node->params));
        }

        return $methods;
    }

    /**
     * @param array<Node\Param> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectParameters(TypeContext $typeContext, array $nodes): array
    {
        $compiler = $this->constantExpressionCompilerProvider->get();
        $parameters = [];

        foreach ($nodes as $node) {
            \assert($node->var instanceof Variable && \is_string($node->var->name));
            $parameters[$node->var->name] = $this->reflectNode($node)
                ->set(Data::Visibility, $this->reflectVisibility($node->flags))
                ->set(Data::Attributes, $this->reflectAttributes($node->attrGroups))
                ->set(Data::Type, new TypeData($this->reflectType(
                    typeContext: $typeContext,
                    node: $node->type,
                    nullable: $node->default instanceof ConstFetch && $node->default->name->toCodeString() === 'null',
                )))
                ->set(Data::ByReference, $node->byRef)
                ->set(Data::DefaultValueExpression, $compiler->compile($node->default))
                ->set(Data::Promoted, $node->flags !== 0)
                ->set(Data::NativeReadonly, (bool) ($node->flags & Class_::MODIFIER_READONLY))
                ->set(Data::Variadic, $node->variadic);
        }

        return $parameters;
    }

    private function reflectVisibility(int $flags): ?Visibility
    {
        return match (true) {
            (bool) ($flags & Class_::MODIFIER_PUBLIC) => Visibility::Public,
            (bool) ($flags & Class_::MODIFIER_PROTECTED) => Visibility::Protected,
            (bool) ($flags & Class_::MODIFIER_PRIVATE) => Visibility::Private,
            default => null,
        };
    }

    /**
     * @return ($node is null ? null : Type)
     */
    private function reflectType(TypeContext $typeContext, null|Name|Identifier|ComplexType $node, bool $nullable = false): ?Type
    {
        if ($node === null) {
            return null;
        }

        if ($nullable) {
            return types::nullable($this->reflectType($typeContext, $node));
        }

        if ($node instanceof NullableType) {
            return types::nullable($this->reflectType($typeContext, $node->type));
        }

        if ($node instanceof UnionType) {
            return types::union(...array_map(
                fn(Node $child): Type => $this->reflectType($typeContext, $child),
                $node->types,
            ));
        }

        if ($node instanceof IntersectionType) {
            return types::intersection(...array_map(
                fn(Node $child): Type => $this->reflectType($typeContext, $child),
                $node->types,
            ));
        }

        if ($node instanceof Identifier) {
            return match ($node->name) {
                'never' => types::never,
                'void' => types::void,
                'null' => types::null,
                'true' => types::true,
                'false' => types::false,
                'bool' => types::bool,
                'int' => types::int,
                'float' => types::float,
                'string' => types::string,
                'array' => types::array,
                'object' => types::object,
                'callable' => types::callable,
                'iterable' => types::iterable,
                'resource' => types::resource,
                'mixed' => types::mixed,
                default => throw new \LogicException(sprintf('Native type "%s" is not supported', $node->name)),
            };
        }

        if ($node instanceof Name) {
            return $typeContext->resolveType($node);
        }

        /** @psalm-suppress MixedArgument */
        throw new \LogicException(sprintf('Type node of class %s is not supported', $node::class));
    }
}
