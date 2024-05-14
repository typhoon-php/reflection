<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParserReflector;

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
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\Reflection\Internal\ClassKind;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Expression\ClassConstantFetch;
use Typhoon\Reflection\Internal\Expression\Expression;
use Typhoon\Reflection\Internal\Expression\ExpressionCompiler;
use Typhoon\Reflection\Internal\Expression\MagicClass;
use Typhoon\Reflection\Internal\Expression\Value;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\Reflection\Internal\TypeContext\TypeContext;
use Typhoon\Reflection\Internal\TypeData;
use Typhoon\Reflection\Internal\UsedMethodAlias;
use Typhoon\Reflection\Internal\Visibility;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ReflectPhpParserNode implements ReflectionHook
{
    public function __construct(
        private ExpressionCompiler $expressionCompiler = new ExpressionCompiler(),
    ) {}

    public function reflect(FunctionId|ClassId|AnonymousClassId $id, TypedMap $data): TypedMap
    {
        $node = $data[Data::Node] ?? null;

        if ($node === null) {
            return $data;
        }

        if ($id instanceof ClassId || $id instanceof AnonymousClassId) {
            \assert($node instanceof ClassLike);

            return $this->reflectClass($data, $node);
        }

        return $data;
    }

    private function reflectClass(TypedMap $data, ClassLike $node): TypedMap
    {
        $typeContext = SetTypeContextVisitor::getNodeTypeContext($node);

        $data = $data
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
        $startLine = $node->getStartLine();
        $endLine = $node->getEndLine();
        $phpDoc = $node->getDocComment()?->getText();

        return (new TypedMap())
            ->set(Data::StartLine, $startLine > 0 ? $startLine : null)
            ->set(Data::EndLine, $endLine > 0 ? $endLine : null)
            ->set(Data::PhpDoc, $phpDoc === '' ? null : $phpDoc);
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
        $uses = [];
        $precedence = [];
        $phpDocs = [];

        foreach ($nodes as $node) {
            $phpDoc = $node->getDocComment()?->getText() ?? '';

            if ($phpDoc !== '') {
                $phpDocs[] = $phpDoc;
            }

            foreach ($node->traits as $name) {
                $uses[$name->toString()] = [];
            }

            foreach ($node->adaptations as $adaptation) {
                if ($adaptation instanceof Precedence) {
                    \assert($adaptation->trait !== null);
                    $precedence[$adaptation->method->name] = $adaptation->trait->toString();
                }
            }
        }

        return (new TypedMap())
            ->set(Data::UnresolvedUses, $uses)
            ->set(Data::UsedMethodPrecedence, $precedence)
            ->set(Data::UsedMethodAliases, $this->reflectTraitAliases($nodes, array_keys($uses)))
            ->set(Data::UsePhpDocs, $phpDocs);
    }

    /**
     * @param array<TraitUse> $nodes
     * @param list<non-empty-string> $traits
     * @return list<UsedMethodAlias>
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
                        $aliases[] = new UsedMethodAlias(
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
                    ->set(Data::AttributeClass, $attr->name->toString())
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
        $arguments = [];

        foreach ($nodes as $node) {
            if ($node->name === null) {
                $arguments[] = $this->expressionCompiler->compile($node->value);
            } else {
                $arguments[$node->name->name] = $this->expressionCompiler->compile($node->value);
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
        $constants = [];

        foreach ($nodes as $node) {
            $data = $this
                ->reflectNode($node)
                ->set(Data::Attributes, $this->reflectAttributes($node->attrGroups))
                ->set(Data::NativeFinal, $node->isFinal())
                ->set(Data::Type, new TypeData($this->reflectType($typeContext, $node->type)))
                ->set(Data::Visibility, $this->reflectVisibility($node->flags));

            foreach ($node->consts as $const) {
                $constants[$const->name->name] = $data->set(Data::ValueExpression, $this->expressionCompiler->compile($const->value));
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
        $cases = [];

        foreach ($nodes as $node) {
            $name = $node->name->name;
            $data = $this
                ->reflectNode($node)
                ->set(Data::Attributes, $this->reflectAttributes($node->attrGroups))
                ->set(Data::NativeFinal, false)
                ->set(Data::EnumCase, true)
                ->set(Data::Type, new TypeData(types::classConstant($typeContext->resolveType(new Name('self')), $name)))
                ->set(Data::Visibility, Visibility::Public)
                ->set(Data::ValueExpression, new ClassConstantFetch(MagicClass::Constant, new Value($name)));

            if ($node->expr !== null) {
                $data = $data->set(Data::EnumBackingValueExpression, $this->expressionCompiler->compile($node->expr));
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
                $default = $this->expressionCompiler->compile($prop->default);

                if ($default === null && $node->type === null) {
                    $default = new Value(null);
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
            $typeContext = SetTypeContextVisitor::getNodeTypeContext($node);
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
                ->set(Data::DefaultValueExpression, $this->expressionCompiler->compile($node->default))
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
