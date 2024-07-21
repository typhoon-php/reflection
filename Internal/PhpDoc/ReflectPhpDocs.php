<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpDoc;

use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueParameterNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasImportTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\ClassReflectionHook;
use Typhoon\Reflection\Internal\ConstantExpression\ConstantExpressionCompiler;
use Typhoon\Reflection\Internal\Context\AnnotatedTypeNames;
use Typhoon\Reflection\Internal\Context\AnnotatedTypesDriver;
use Typhoon\Reflection\Internal\Context\Context;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\ClassKind;
use Typhoon\Reflection\Internal\Data\TypeData;
use Typhoon\Reflection\Internal\Data\Visibility;
use Typhoon\Reflection\Internal\FunctionReflectionHook;
use Typhoon\Reflection\Internal\PhpDoc\ContextualPhpDocTypeReflector as TypeReflector;
use Typhoon\Reflection\Internal\Reflector;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\Variance;
use function Typhoon\Reflection\Internal\map;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ReflectPhpDocs implements AnnotatedTypesDriver, ClassReflectionHook, FunctionReflectionHook
{
    public function __construct(
        private readonly PhpDocParser $parser = new PhpDocParser(),
    ) {}

    public function reflectAnnotatedTypeNames(FunctionLike|ClassLike $node): AnnotatedTypeNames
    {
        $phpDoc = $this->parsePhpDoc($node->getDocComment()?->getText());

        if ($phpDoc === null) {
            return new AnnotatedTypeNames();
        }

        return new AnnotatedTypeNames(
            templateNames: array_map(
                static fn(PhpDocTagNode $node): string => $node->value->name,
                $phpDoc->templateTags(),
            ),
            aliasNames: [
                ...array_column($phpDoc->typeAliases(), 'alias'),
                ...array_map(
                    static fn(TypeAliasImportTagValueNode $node): string => $node->importedAs ?? $node->importedAlias,
                    $phpDoc->typeAliasImports(),
                ),
            ],
        );
    }

    public function process(NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, Reflector $reflector): TypedMap
    {
        if ($id instanceof NamedFunctionId || $id instanceof AnonymousFunctionId) {
            return $this->reflectFunctionLike($data);
        }

        return $this->reflectClass($data);
    }

    private function reflectFunctionLike(TypedMap $data, bool $constructor = false): TypedMap
    {
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc === null) {
            return $data;
        }

        $typeReflector = new TypeReflector($data[Data::Context]);
        $paramTypes = $phpDoc->paramTypes();

        return $data
            ->with(Data::Templates, $this->reflectTemplates($typeReflector, $data[Data::PhpDocStartLine], $phpDoc->templateTags()))
            ->with(Data::Parameters, map(
                $data[Data::Parameters],
                function (TypedMap $parameter, string $name) use ($constructor, $typeReflector, $paramTypes): TypedMap {
                    $type = $this->addAnnotatedType($typeReflector, $parameter[Data::Type], $paramTypes[$name] ?? null);
                    $parameter = $parameter->with(Data::Type, $type);

                    if ($constructor && $parameter[Data::Promoted]) {
                        return $this->reflectNativeProperty($typeReflector, $parameter);
                    }

                    return $parameter;
                },
            ))
            ->with(Data::Type, $this->addAnnotatedType($typeReflector, $data[Data::Type], $phpDoc->returnType()))
            ->with(Data::ThrowsType, $this->reflectThrowsType($typeReflector, $phpDoc));
    }

    private function reflectClass(TypedMap $data): TypedMap
    {
        $typeReflector = new TypeReflector($data[Data::Context]);
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc !== null) {
            $data = $data
                ->with(Data::AnnotatedFinal, $data[Data::AnnotatedFinal] || $phpDoc->hasFinal())
                ->with(Data::AnnotatedFinal, $data[Data::AnnotatedReadonly] || $phpDoc->hasReadonly())
                ->with(Data::Templates, $this->reflectTemplates($typeReflector, $data[Data::PhpDocStartLine], $phpDoc->templateTags()))
                ->with(Data::Aliases, $this->reflectAliases($typeReflector, $data[Data::PhpDocStartLine], $phpDoc))
                ->with(Data::UnresolvedParent, $this->reflectParent($typeReflector, $data, $phpDoc))
                ->with(Data::UnresolvedInterfaces, $this->reflectInterfaces($typeReflector, $data, $phpDoc))
                ->with(Data::UnresolvedTraits, $this->reflectUses($typeReflector, $data));
        }

        return $data
            ->with(Data::Constants, array_map(
                fn(TypedMap $constant): TypedMap => $this->reflectConstant($typeReflector, $constant),
                $data[Data::Constants],
            ))
            ->with(Data::Properties, $this->reflectProperties($typeReflector, $data[Data::Properties], $phpDoc))
            ->with(Data::Methods, $this->reflectMethods(
                classContext: $data[Data::Context],
                compiler: $data[Data::ConstantExpressionCompiler],
                methods: $data[Data::Methods],
                phpDocStartLine: $data[Data::PhpDocStartLine],
                classPhpDoc: $phpDoc,
            ));
    }

    /**
     * @param ?positive-int $phpDocStartLine
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectAliases(TypeReflector $typeReflector, ?int $phpDocStartLine, PhpDoc $phpDoc): array
    {
        $aliases = [];

        foreach ($phpDoc->typeAliases() as $alias) {
            $aliases[$alias->alias] = $this
                ->reflectNodeLines($alias, $phpDocStartLine)
                ->with(Data::AliasType, $typeReflector->reflectType($alias->type));
        }

        foreach ($phpDoc->typeAliasImports() as $aliasImport) {
            $aliases[$aliasImport->importedAs ?? $aliasImport->importedAlias] = (new TypedMap())
                ->withMap($this->reflectNodeLines($aliasImport, $phpDocStartLine))
                ->with(Data::AliasType, types::classAlias($typeReflector->resolveClass($aliasImport->importedFrom), $aliasImport->importedAlias));
        }

        return $aliases;
    }

    /**
     * @param ?positive-int $phpDocStartLine
     * @param list<PhpDocTagNode<TemplateTagValueNode>|TemplateTagValueNode> $templateTags
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectTemplates(TypeReflector $typeReflector, ?int $phpDocStartLine, array $templateTags): array
    {
        $templates = [];

        foreach ($templateTags as $templateTag) {
            if ($templateTag instanceof PhpDocTagNode) {
                $variance = match (true) {
                    str_ends_with($templateTag->name, 'covariant') => Variance::Covariant,
                    str_ends_with($templateTag->name, 'contravariant') => Variance::Contravariant,
                    default => Variance::Invariant,
                };
                $templateTag = $templateTag->value;
            } else {
                $variance = Variance::Invariant;
            }

            $templates[$templateTag->name] = $this
                ->reflectNodeLines($templateTag, $phpDocStartLine)
                ->with(Data::Constraint, $typeReflector->reflectType($templateTag->bound) ?? types::mixed)
                ->with(Data::Variance, $variance);
        }

        return $templates;
    }

    /**
     * @return ?array{non-empty-string, list<Type>}
     */
    private function reflectParent(TypeReflector $typeReflector, TypedMap $data, PhpDoc $phpDoc): ?array
    {
        $parent = $data[Data::UnresolvedParent];

        if ($parent === null) {
            return null;
        }

        foreach ($phpDoc->extendedTypes() as $type) {
            if ($parent[0] === $typeReflector->resolveClass($type->type)) {
                $parent[1] = array_map($typeReflector->reflectType(...), $type->genericTypes);
            }
        }

        return $parent;
    }

    /**
     * @return array<non-empty-string, list<Type>>
     */
    private function reflectInterfaces(TypeReflector $typeReflector, TypedMap $data, PhpDoc $phpDoc): array
    {
        $interfaces = $data[Data::UnresolvedInterfaces];

        if ($interfaces === []) {
            return [];
        }

        $types = $data[Data::ClassKind] === ClassKind::Interface ? $phpDoc->extendedTypes() : $phpDoc->implementedTypes();

        foreach ($types as $type) {
            $name = $typeReflector->resolveClass($type->type);

            if (isset($interfaces[$name])) {
                $interfaces[$name] = array_map($typeReflector->reflectType(...), $type->genericTypes);
            }
        }

        return $interfaces;
    }

    /**
     * @return array<non-empty-string, list<Type>>
     */
    private function reflectUses(TypeReflector $typeReflector, TypedMap $data): array
    {
        $uses = $data[Data::UnresolvedTraits];

        if ($uses === []) {
            return [];
        }

        foreach ($data[Data::UsePhpDocs] as $usePhpDoc) {
            $usePhpDoc = $this->parsePhpDoc($usePhpDoc);

            foreach ($usePhpDoc->usedTypes() as $type) {
                $name = $typeReflector->resolveClass($type->type);

                if (isset($uses[$name])) {
                    $uses[$name] = array_map($typeReflector->reflectType(...), $type->genericTypes);
                }
            }
        }

        return $uses;
    }

    private function reflectConstant(TypeReflector $typeReflector, TypedMap $data): TypedMap
    {
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc === null) {
            return $data;
        }

        return $data
            ->with(Data::AnnotatedFinal, $data[Data::AnnotatedFinal] || $phpDoc->hasFinal())
            ->with(Data::Type, $this->addAnnotatedType($typeReflector, $data[Data::Type], $phpDoc->varType()));
    }

    /**
     * @param array<non-empty-string, TypedMap> $properties
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectProperties(TypeReflector $typeReflector, array $properties, ?PhpDoc $classPhpDoc): array
    {
        foreach ($properties as $name => $property) {
            $properties[$name] = $this->reflectNativeProperty($typeReflector, $property);
        }

        if ($classPhpDoc === null) {
            return $properties;
        }

        foreach ($classPhpDoc->propertyTags() as $tag) {
            $name = ltrim($tag->value->propertyName, '$');

            if ($name !== '' && !isset($properties[$name])) {
                $properties[$name] = $this->reflectPhpDocProperty($typeReflector, $tag);
            }
        }

        return $properties;
    }

    private function reflectNativeProperty(TypeReflector $typeReflector, TypedMap $data): TypedMap
    {
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc === null) {
            return $data;
        }

        return $data
            ->with(Data::AnnotatedReadonly, $data[Data::AnnotatedReadonly] || $phpDoc->hasReadonly())
            ->with(Data::Type, $this->addAnnotatedType($typeReflector, $data[Data::Type], $phpDoc->varType()));
    }

    /**
     * @param PhpDocTagNode<PropertyTagValueNode> $tag
     */
    private function reflectPhpDocProperty(TypeReflector $typeReflector, PhpDocTagNode $tag): TypedMap
    {
        return (new TypedMap())
            ->with(Data::Annotated, true)
            ->with(Data::Visibility, Visibility::Public)
            ->with(Data::AnnotatedReadonly, str_contains($tag->name, 'read'))
            ->with(Data::Type, new TypeData(annotated: $typeReflector->reflectType($tag->value->type)));
    }

    /**
     * @param array<non-empty-string, TypedMap> $methods
     * @param ?positive-int $phpDocStartLine
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectMethods(Context $classContext, ConstantExpressionCompiler $compiler, array $methods, ?int $phpDocStartLine, ?PhpDoc $classPhpDoc): array
    {
        foreach ($methods as $name => $method) {
            $methods[$name] = $this->reflectFunctionLike($method, $name === '__construct');
        }

        if ($classPhpDoc === null) {
            return $methods;
        }

        foreach ($classPhpDoc->methods() as $tag) {
            if (!isset($method[$tag->methodName])) {
                $methods[$tag->methodName] = $this->reflectPhpDocMethod($classContext, $compiler, $tag, $phpDocStartLine);
            }
        }

        return $methods;
    }

    /**
     * @param ?positive-int $phpDocStartLine
     */
    private function reflectPhpDocMethod(Context $classContext, ConstantExpressionCompiler $compiler, MethodTagValueNode $tag, ?int $phpDocStartLine): TypedMap
    {
        $classContext = $classContext->enterMethod($tag->methodName, array_column($tag->templateTypes, 'name'));
        $typeReflector = new TypeReflector($classContext);

        return (new TypedMap())
            ->with(Data::Context, $classContext)
            ->with(Data::Annotated, true)
            ->with(Data::Templates, $this->reflectTemplates($typeReflector, $phpDocStartLine, $tag->templateTypes))
            ->with(Data::Visibility, Visibility::Public)
            ->with(Data::Static, $tag->isStatic)
            ->with(Data::Type, new TypeData(annotated: $typeReflector->reflectType($tag->returnType)))
            ->with(Data::Parameters, $this->reflectPhpDocMethodParameters($classContext, $typeReflector, $compiler, $phpDocStartLine, $tag->parameters));
    }

    /**
     * @param ?positive-int $phpDocStartLine
     * @param array<MethodTagValueParameterNode> $tags
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectPhpDocMethodParameters(Context $context, TypeReflector $typeReflector, ConstantExpressionCompiler $compiler, ?int $phpDocStartLine, array $tags): array
    {
        $parameters = [];

        foreach ($tags as $tag) {
            $name = trim($tag->parameterName, '$');
            \assert($name !== '', 'Parameter name must not be empty');

            $parameters[$name] = (new TypedMap())
                ->with(Data::Type, new TypeData(annotated: $typeReflector->reflectType($tag->type)))
                ->with(Data::ByReference, $tag->isReference)
                ->with(Data::Variadic, $tag->isVariadic)
                ->with(Data::DefaultValueExpression, $compiler->compilePHPStan($context, $phpDocStartLine, $tag->defaultValue));
        }

        return $parameters;
    }

    private function addAnnotatedType(TypeReflector $typeReflector, TypeData $type, ?TypeNode $node): TypeData
    {
        if ($node === null) {
            return $type;
        }

        return $type->withAnnotated($typeReflector->reflectType($node));
    }

    private function reflectThrowsType(TypeReflector $typeReflector, PhpDoc $phpDoc): ?Type
    {
        $throwsTypes = $phpDoc->throwsTypes();

        if ($throwsTypes === []) {
            return null;
        }

        return types::union(...array_map($typeReflector->reflectType(...), $throwsTypes));
    }

    /**
     * @param ?positive-int $phpDocStartLine
     */
    private function reflectNodeLines(Node $node, ?int $phpDocStartLine): TypedMap
    {
        if ($phpDocStartLine === null) {
            return new TypedMap();
        }

        $startLine = $node->getAttribute('startLine');
        $endLine = $node->getAttribute('endLine');

        if (\is_int($startLine) && $startLine > 0) {
            return (new TypedMap())
                ->with(Data::StartLine, $phpDocStartLine - 1 + $startLine)
                ->with(Data::EndLine, $phpDocStartLine - 1 + (\is_int($endLine) && $endLine > 0 ? $endLine : $startLine));
        }

        return new TypedMap();
    }

    /**
     * @return ($text is null ? null : PhpDoc)
     */
    private function parsePhpDoc(?string $text): ?PhpDoc
    {
        if ($text === null || $text === '') {
            return null;
        }

        return $this->parser->parse($text);
    }
}
