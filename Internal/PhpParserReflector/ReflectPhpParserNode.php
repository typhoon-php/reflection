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
use Typhoon\DeclarationId\DeclarationId;
use Typhoon\Reflection\Internal\ClassKind;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Expression\ClassConstantFetch;
use Typhoon\Reflection\Internal\Expression\Expression;
use Typhoon\Reflection\Internal\Expression\ExpressionCompiler;
use Typhoon\Reflection\Internal\Expression\Value;
use Typhoon\Reflection\Internal\InheritedName;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\Reflection\Internal\TraitMethodAlias;
use Typhoon\Reflection\Internal\UsedName;
use Typhoon\Reflection\Internal\Visibility;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\TypeContext\TypeContext;
use Typhoon\TypeContext\UnqualifiedName;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ReflectPhpParserNode implements ReflectionHook
{
    public function reflect(DeclarationId $id, TypedMap $data): TypedMap
    {
        if ($id instanceof ClassId || $id instanceof AnonymousClassId) {
            return $this->reflectClass($id, $data);
        }

        return $data;
    }

    private function reflectClass(ClassId|AnonymousClassId $id, TypedMap $data): TypedMap
    {
        $node = $data[Data::Node()];
        \assert($node instanceof ClassLike);
        $typeContext = $data[Data::TypeContext()];
        $expressionCompiler = new ExpressionCompiler(
            typeContext: $typeContext,
            file: $data[Data::File()] ?? null,
            class: $id->name,
            trait: $node instanceof Trait_,
        );

        $data = $data
            ->withAllFrom($this->reflectNode($node))
            ->with(Data::Attributes(), $this->reflectAttributes($typeContext, $expressionCompiler, $node->attrGroups));

        if ($node instanceof Class_) {
            $data = $data
                ->with(Data::ClassKind(), ClassKind::Class_)
                ->with(Data::UnresolvedParent(), $this->reflectParentClass($typeContext, $node->extends))
                ->with(Data::UnresolvedInterfaces(), $this->reflectInterfaces($typeContext, $node->implements))
                ->withAllFrom($this->reflectTraitUses($typeContext, $node->getTraitUses()))
                ->with(Data::Abstract(), $node->isAbstract())
                ->with(Data::NativeReadonly(), $node->isReadonly())
                ->with(Data::NativeFinal(), $node->isFinal())
                ->with(Data::ClassConstants(), $this->reflectConstants($typeContext, $expressionCompiler, $node->getConstants()))
                ->with(Data::Properties(), $this->reflectProperties($typeContext, $expressionCompiler, $node->getProperties(), $node->isReadonly()))
                ->with(Data::Methods(), $this->reflectMethods($typeContext, $expressionCompiler, $node->getMethods()));

            return $this->reflectPromotedProperties($data);
        }

        if ($node instanceof Interface_) {
            return $data
                ->with(Data::ClassKind(), ClassKind::Interface)
                ->with(Data::UnresolvedInterfaces(), $this->reflectInterfaces($typeContext, $node->extends))
                ->with(Data::ClassConstants(), $this->reflectConstants($typeContext, $expressionCompiler, $node->getConstants()))
                ->with(Data::Methods(), $this->reflectMethods($typeContext, $expressionCompiler, $node->getMethods(), abstract: true));
        }

        if ($node instanceof Enum_) {
            $scalarType = $this->reflectType($typeContext, $node->scalarType);

            return $data
                ->with(Data::ClassKind(), ClassKind::Enum)
                ->with(Data::UnresolvedInterfaces(), $this->reflectInterfaces($typeContext, $node->implements, backedEnum: $scalarType !== null))
                ->withAllFrom($this->reflectTraitUses($typeContext, $node->getTraitUses()))
                ->with(Data::NativeFinal(), true)
                ->with(Data::NativeType(), $scalarType)
                ->with(Data::ClassConstants(), [
                    ...$this->reflectConstants($typeContext, $expressionCompiler, $node->getConstants()),
                    ...$this->reflectEnumCases($typeContext, $expressionCompiler, array_filter(
                        $node->stmts,
                        static fn(Node $node): bool => $node instanceof EnumCase,
                    )),
                ])
                ->with(Data::Properties(), $this->reflectEnumProperties($scalarType))
                ->with(Data::Methods(), $this->reflectMethods($typeContext, $expressionCompiler, $node->getMethods(), enumClass: $id->name, enumType: $scalarType));
        }

        if ($node instanceof Trait_) {
            $data = $data
                ->with(Data::ClassKind(), ClassKind::Trait)
                ->withAllFrom($this->reflectTraitUses($typeContext, $node->getTraitUses()))
                ->with(Data::ClassConstants(), $this->reflectConstants($typeContext, $expressionCompiler, $node->getConstants()))
                ->with(Data::Properties(), $this->reflectProperties($typeContext, $expressionCompiler, $node->getProperties()))
                ->with(Data::Methods(), $this->reflectMethods($typeContext, $expressionCompiler, $node->getMethods()));

            return $this->reflectPromotedProperties($data);
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

    private function reflectParentClass(TypeContext $typeContext, ?Name $name): ?InheritedName
    {
        if ($name === null) {
            return null;
        }

        return new InheritedName($typeContext->resolveClassName($name)->toStringWithoutSlash());
    }

    /**
     * @param array<Name> $names
     * @return list<InheritedName>
     */
    private function reflectInterfaces(TypeContext $typeContext, array $names, ?bool $backedEnum = null): array
    {
        $interfaces = [];

        foreach ($names as $name) {
            $interfaces[] = new InheritedName($typeContext->resolveClassName($name)->toStringWithoutSlash());
        }

        if ($backedEnum !== null) {
            $interfaces[] = new InheritedName(\UnitEnum::class);

            if ($backedEnum) {
                $interfaces[] = new InheritedName(\BackedEnum::class);
            }
        }

        return $interfaces;
    }

    /**
     * @param array<TraitUse> $nodes
     */
    private function reflectTraitUses(TypeContext $typeContext, array $nodes): TypedMap
    {
        $traits = [];
        $precedence = [];

        foreach ($nodes as $node) {
            foreach ($node->traits as $name) {
                $traits[] = $typeContext->resolveClassName($name)->toStringWithoutSlash();
            }

            foreach ($node->adaptations as $adaptation) {
                if ($adaptation instanceof Precedence) {
                    \assert($adaptation->trait !== null);
                    $precedence[$adaptation->method->name] = $typeContext->resolveClassName($adaptation->trait)->toStringWithoutSlash();
                }
            }
        }

        return (new TypedMap())
            ->with(Data::UnresolvedTraits(), array_map(
                static fn(string $name): UsedName => new UsedName($name),
                $traits,
            ))
            ->with(Data::TraitMethodPrecedence(), $precedence)
            ->with(Data::TraitMethodAliases(), $this->reflectTraitAliases($typeContext, $nodes, $traits));
    }

    /**
     * @param array<TraitUse> $nodes
     * @param list<non-empty-string> $traits
     * @return list<TraitMethodAlias>
     */
    private function reflectTraitAliases(TypeContext $typeContext, array $nodes, array $traits): array
    {
        $aliases = [];

        foreach ($nodes as $node) {
            foreach ($node->adaptations as $adaptation) {
                if ($adaptation instanceof Alias) {
                    if ($adaptation->trait === null) {
                        $aliasTraits = $traits;
                    } else {
                        $aliasTraits = [$typeContext->resolveClassName($adaptation->trait)->toStringWithoutSlash()];
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
    private function reflectAttributes(TypeContext $typeContext, ExpressionCompiler $expressionCompiler, array $attributeGroups): array
    {
        $attributes = [];

        foreach ($attributeGroups as $attributeGroup) {
            foreach ($attributeGroup->attrs as $attr) {
                $attributes[] = $this->reflectNode($attr)
                    ->with(Data::AttributeClass(), $typeContext->resolveClassName($attr->name)->toStringWithoutSlash())
                    ->with(Data::ArgumentExpressions(), $this->reflectArguments($expressionCompiler, $attr->args));
            }
        }

        return $attributes;
    }

    /**
     * @param array<Node\Arg> $nodes
     * @return array<Expression>
     */
    private function reflectArguments(ExpressionCompiler $expressionCompiler, array $nodes): array
    {
        $arguments = [];

        foreach ($nodes as $node) {
            if ($node->name === null) {
                $arguments[] = $expressionCompiler->compile($node->value);
            } else {
                $arguments[$node->name->name] = $expressionCompiler->compile($node->value);
            }
        }

        return $arguments;
    }

    /**
     * @param array<ClassConst> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectConstants(TypeContext $typeContext, ExpressionCompiler $expressionCompiler, array $nodes): array
    {
        $constants = [];

        foreach ($nodes as $node) {
            $data = $this
                ->reflectNode($node)
                ->with(Data::Attributes(), $this->reflectAttributes($typeContext, $expressionCompiler, $node->attrGroups))
                ->with(Data::NativeFinal(), $node->isFinal())
                ->with(Data::NativeType(), $this->reflectType($typeContext, $node->type))
                ->with(Data::Visibility(), $this->reflectVisibility($node->flags));

            foreach ($node->consts as $const) {
                $constants[$const->name->name] = $data->with(Data::ValueExpression(), $expressionCompiler->compile($const->value));
            }
        }

        return $constants;
    }

    /**
     * @param array<EnumCase> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectEnumCases(TypeContext $typeContext, ExpressionCompiler $expressionCompiler, array $nodes): array
    {
        $class = $typeContext->resolveClassName(UnqualifiedName::self())->toStringWithoutSlash();
        $cases = [];

        foreach ($nodes as $node) {
            $name = $node->name->name;
            $data = $this
                ->reflectNode($node)
                ->with(Data::Attributes(), $this->reflectAttributes($typeContext, $expressionCompiler, $node->attrGroups))
                ->with(Data::NativeFinal(), false)
                ->with(Data::EnumCase(), true)
                ->with(Data::NativeType(), types::classConstant(types::object($class), $name))
                ->with(Data::Visibility(), Visibility::Public)
                ->with(Data::ValueExpression(), new ClassConstantFetch(new Value($class), new Value($name)));

            if ($node->expr !== null) {
                $data = $data->with(Data::BackingValueExpression(), $expressionCompiler->compile($node->expr));
            }

            $cases[$name] = $data;
        }

        return $cases;
    }

    private function reflectPromotedProperties(TypedMap $data): TypedMap
    {
        $methods = $data[Data::Methods()];

        if (!isset($methods['__construct'])) {
            return $data;
        }

        $properties = $data[Data::Properties()];

        foreach ($methods['__construct'][Data::Parameters()] as $name => $parameter) {
            if ($parameter[Data::Promoted()]) {
                $properties[$name] = $parameter;
            }
        }

        return $data->with(Data::Properties(), $properties);
    }

    /**
     * @param array<Property> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectProperties(TypeContext $typeContext, ExpressionCompiler $expressionCompiler, array $nodes, bool $readonly = false): array
    {
        $properties = [];

        foreach ($nodes as $node) {
            $data = $this
                ->reflectNode($node)
                ->with(Data::Attributes(), $this->reflectAttributes($typeContext, $expressionCompiler, $node->attrGroups))
                ->with(Data::Static(), $node->isStatic())
                ->with(Data::NativeReadonly(), $readonly || $node->isReadonly())
                ->with(Data::NativeType(), $this->reflectType($typeContext, $node->type))
                ->with(Data::Visibility(), $this->reflectVisibility($node->flags));

            foreach ($node->props as $prop) {
                $default = $expressionCompiler->compile($prop->default);

                if ($default === null && $node->type === null) {
                    $default = new Value(null);
                }

                $properties[$prop->name->name] = $data->with(Data::DefaultValueExpression(), $default);
            }
        }

        return $properties;
    }

    /**
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectEnumProperties(?Type $enumType): array
    {
        $properties = [
            'name' => (new TypedMap())
                ->with(Data::NativeReadonly(), true)
                ->with(Data::NativeType(), types::string)
                ->with(Data::Visibility(), Visibility::Public),
        ];

        if ($enumType !== null) {
            $properties['value'] = (new TypedMap())
                ->with(Data::NativeReadonly(), true)
                ->with(Data::NativeType(), $enumType)
                ->with(Data::Visibility(), Visibility::Public);
        }

        return $properties;
    }

    /**
     * @param array<ClassMethod> $nodes
     * @param ?non-empty-string $enumClass
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectMethods(
        TypeContext $typeContext,
        ExpressionCompiler $expressionCompiler,
        array $nodes,
        bool $abstract = false,
        ?string $enumClass = null,
        ?Type $enumType = null,
    ): array {
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
                ->with(Data::Attributes(), $this->reflectAttributes($typeContext, $expressionCompiler, $node->attrGroups))
                ->with(Data::Parameters(), $this->reflectParameters($typeContext, $expressionCompiler, $node->params));
        }

        if ($enumClass !== null) {
            $methods['cases'] = (new TypedMap())
                ->with(Data::Static(), true)
                ->with(Data::NativeType(), types::array)
                ->with(Data::AnnotatedType(), types::list(types::static($enumClass)))
                ->with(Data::Visibility(), Visibility::Public)
                ->with(Data::WrittenInC(), true);

            if ($enumType !== null) {
                $methods['from'] = (new TypedMap())
                    ->with(Data::Static(), true)
                    ->with(Data::NativeType(), types::static($enumClass))
                    ->with(Data::Visibility(), Visibility::Public)
                    ->with(Data::WrittenInC(), true)
                    ->with(Data::Parameters(), [
                        'value' => (new TypedMap())
                            ->with(Data::NativeType(), types::arrayKey)
                            ->with(Data::AnnotatedType(), $enumType),
                    ]);
                $methods['tryFrom'] = $methods['from']
                    ->with(Data::NativeType(), types::nullable(types::static($enumClass)));
            }
        }

        return $methods;
    }

    /**
     * @param array<Node\Param> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectParameters(TypeContext $typeContext, ExpressionCompiler $expressionCompiler, array $nodes): array
    {
        $parameters = [];

        foreach ($nodes as $node) {
            \assert($node->var instanceof Variable && \is_string($node->var->name));
            $parameters[$node->var->name] = $this->reflectNode($node)
                ->with(Data::Visibility(), $this->reflectVisibility($node->flags))
                ->with(Data::Attributes(), $this->reflectAttributes($typeContext, $expressionCompiler, $node->attrGroups))
                ->with(Data::NativeType(), $this->reflectType(
                    typeContext: $typeContext,
                    node: $node->type,
                    nullable: $node->default instanceof ConstFetch && $node->default->name->toCodeString() === 'null',
                ))
                ->with(Data::ByReference(), $node->byRef)
                ->with(Data::DefaultValueExpression(), $expressionCompiler->compile($node->default))
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
