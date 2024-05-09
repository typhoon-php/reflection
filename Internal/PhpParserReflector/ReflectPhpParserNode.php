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
use Typhoon\Reflection\Internal\InheritedName;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\Reflection\Internal\TraitMethodAlias;
use Typhoon\Reflection\Internal\UsedName;
use Typhoon\Reflection\Internal\Visibility;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\TypeContext\TypeContext;
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
        $node = $data[Data::Node()] ?? null;

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
            ->withAllFrom($this->reflectNode($node))
            ->with(Data::TypeContext(), $typeContext)
            ->with(Data::Attributes(), $this->reflectAttributes($node->attrGroups));

        if ($node instanceof Class_) {
            return $data
                ->with(Data::ClassKind(), ClassKind::Class_)
                ->with(Data::UnresolvedParent(), $node->extends === null ? null : new InheritedName($node->extends->toString()))
                ->with(Data::UnresolvedInterfaces(), $this->reflectInterfaces($node->implements))
                ->withAllFrom($this->reflectTraitUses($node->getTraitUses()))
                ->with(Data::Abstract(), $node->isAbstract())
                ->with(Data::NativeReadonly(), $node->isReadonly())
                ->with(Data::NativeFinal(), $node->isFinal())
                ->with(Data::ClassConstants(), $this->reflectConstants($typeContext, $node->getConstants()))
                ->with(Data::Properties(), $this->reflectProperties($typeContext, $node->getProperties(), $node->isReadonly()))
                ->with(Data::Methods(), $this->reflectMethods($typeContext, $node->getMethods()));
        }

        if ($node instanceof Interface_) {
            return $data
                ->with(Data::ClassKind(), ClassKind::Interface)
                ->with(Data::UnresolvedInterfaces(), $this->reflectInterfaces($node->extends))
                ->with(Data::ClassConstants(), $this->reflectConstants($typeContext, $node->getConstants()))
                ->with(Data::Methods(), $this->reflectMethods($typeContext, $node->getMethods(), abstract: true));
        }

        if ($node instanceof Enum_) {
            $scalarType = $this->reflectType($typeContext, $node->scalarType);

            return $data
                ->with(Data::ClassKind(), ClassKind::Enum)
                ->with(Data::UnresolvedInterfaces(), $this->reflectInterfaces($node->implements))
                ->withAllFrom($this->reflectTraitUses($node->getTraitUses()))
                ->with(Data::NativeFinal(), true)
                ->with(Data::NativeType(), $scalarType)
                ->with(Data::ClassConstants(), [
                    ...$this->reflectConstants($typeContext, $node->getConstants()),
                    ...$this->reflectEnumCases($typeContext, array_filter(
                        $node->stmts,
                        static fn(Node $node): bool => $node instanceof EnumCase,
                    )),
                ])
                ->with(Data::Methods(), $this->reflectMethods($typeContext, $node->getMethods()));
        }

        if ($node instanceof Trait_) {
            return $data
                ->with(Data::ClassKind(), ClassKind::Trait)
                ->withAllFrom($this->reflectTraitUses($node->getTraitUses()))
                ->with(Data::ClassConstants(), $this->reflectConstants($typeContext, $node->getConstants()))
                ->with(Data::Properties(), $this->reflectProperties($typeContext, $node->getProperties()))
                ->with(Data::Methods(), $this->reflectMethods($typeContext, $node->getMethods()));
        }

        return $data;
    }

    private function reflectNode(Node $node): TypedMap
    {
        $startLine = $node->getStartLine();
        $endLine = $node->getEndLine();
        $phpDoc = $node->getDocComment()?->getText();

        return (new TypedMap())
            ->with(Data::StartLine(), $startLine > 0 ? $startLine : null)
            ->with(Data::EndLine(), $endLine > 0 ? $endLine : null)
            ->with(Data::PhpDoc(), $phpDoc === null || $phpDoc === '' ? null : $phpDoc);
    }

    /**
     * @param array<Name> $names
     * @return list<InheritedName>
     */
    private function reflectInterfaces(array $names): array
    {
        $interfaces = [];

        foreach ($names as $name) {
            $interfaces[] = new InheritedName($name->toString());
        }

        return $interfaces;
    }

    /**
     * @param array<TraitUse> $nodes
     */
    private function reflectTraitUses(array $nodes): TypedMap
    {
        $traits = [];
        $precedence = [];

        foreach ($nodes as $node) {
            foreach ($node->traits as $name) {
                $traits[] = $name->toString();
            }

            foreach ($node->adaptations as $adaptation) {
                if ($adaptation instanceof Precedence) {
                    \assert($adaptation->trait !== null);
                    $precedence[$adaptation->method->name] = $adaptation->trait->toString();
                }
            }
        }

        return (new TypedMap())
            ->with(Data::UnresolvedTraits(), array_map(
                static fn(string $name): UsedName => new UsedName($name),
                $traits,
            ))
            ->with(Data::TraitMethodPrecedence(), $precedence)
            ->with(Data::TraitMethodAliases(), $this->reflectTraitAliases($nodes, $traits));
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
                    ->with(Data::AttributeClass(), $attr->name->toString())
                    ->with(Data::ArgumentExpressions(), $this->reflectArguments($attr->args));
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
                ->with(Data::Attributes(), $this->reflectAttributes($node->attrGroups))
                ->with(Data::NativeFinal(), $node->isFinal())
                ->with(Data::NativeType(), $this->reflectType($typeContext, $node->type))
                ->with(Data::Visibility(), $this->reflectVisibility($node->flags));

            foreach ($node->consts as $const) {
                $constants[$const->name->name] = $data->with(Data::ValueExpression(), $this->expressionCompiler->compile($const->value));
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
                ->with(Data::Attributes(), $this->reflectAttributes($node->attrGroups))
                ->with(Data::NativeFinal(), false)
                ->with(Data::EnumCase(), true)
                ->with(Data::NativeType(), types::classConstant($typeContext->resolveType(new Name('self')), $name))
                ->with(Data::Visibility(), Visibility::Public)
                ->with(Data::ValueExpression(), new ClassConstantFetch(MagicClass::Constant, new Value($name)));

            if ($node->expr !== null) {
                $data = $data->with(Data::BackingValueExpression(), $this->expressionCompiler->compile($node->expr));
            }

            $cases[$name] = $data;
        }

        return $cases;
    }

    /**
     * @param array<Property> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectProperties(TypeContext $typeContext, array $nodes, bool $readonly = false): array
    {
        $properties = [];

        foreach ($nodes as $node) {
            $data = $this
                ->reflectNode($node)
                ->with(Data::Attributes(), $this->reflectAttributes($node->attrGroups))
                ->with(Data::Static(), $node->isStatic())
                ->with(Data::NativeReadonly(), $readonly || $node->isReadonly())
                ->with(Data::NativeType(), $this->reflectType($typeContext, $node->type))
                ->with(Data::Visibility(), $this->reflectVisibility($node->flags));

            foreach ($node->props as $prop) {
                $default = $this->expressionCompiler->compile($prop->default);

                if ($default === null && $node->type === null) {
                    $default = new Value(null);
                }

                $properties[$prop->name->name] = $data->with(Data::DefaultValueExpression(), $default);
            }
        }

        return $properties;
    }

    /**
     * @param array<ClassMethod> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectMethods(TypeContext $typeContext, array $nodes, bool $abstract = false): array
    {
        $methods = [];

        foreach ($nodes as $node) {
            $methods[$node->name->name] = $this->reflectNode($node)
                ->with(Data::Static(), $node->isStatic())
                ->with(Data::NativeFinal(), $node->isFinal())
                ->with(Data::Abstract(), $abstract || $node->isAbstract())
                ->with(Data::NativeType(), $this->reflectType($typeContext, $node->returnType))
                ->with(Data::Visibility(), $this->reflectVisibility($node->flags))
                ->with(Data::ByReference(), $node->byRef)
                ->with(Data::Generator(), IsGeneratorChecker::check($node))
                ->with(Data::Attributes(), $this->reflectAttributes($node->attrGroups))
                ->with(Data::Parameters(), $this->reflectParameters($typeContext, $node->params));
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
                ->with(Data::Visibility(), $this->reflectVisibility($node->flags))
                ->with(Data::Attributes(), $this->reflectAttributes($node->attrGroups))
                ->with(Data::NativeType(), $this->reflectType(
                    typeContext: $typeContext,
                    node: $node->type,
                    nullable: $node->default instanceof ConstFetch && $node->default->name->toCodeString() === 'null',
                ))
                ->with(Data::ByReference(), $node->byRef)
                ->with(Data::DefaultValueExpression(), $this->expressionCompiler->compile($node->default))
                ->with(Data::Promoted(), $node->flags !== 0)
                ->with(Data::NativeReadonly(), (bool) ($node->flags & Class_::MODIFIER_READONLY))
                ->with(Data::Variadic(), $node->variadic);
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
