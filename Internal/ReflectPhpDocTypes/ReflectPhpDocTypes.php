<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ReflectPhpDocTypes;

use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasImportTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\Reflection\Internal\ClassKind;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\Reflection\Internal\TypeContext\AnnotatedTypesDriver;
use Typhoon\Reflection\Internal\TypeContext\NameParser;
use Typhoon\Reflection\Internal\TypeContext\TypeDeclarations;
use Typhoon\Reflection\Internal\TypeData;
use Typhoon\Type\types;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ReflectPhpDocTypes implements ReflectionHook, AnnotatedTypesDriver
{
    public function __construct(
        private readonly PhpDocParser $parser = new PhpDocParser(),
    ) {}

    public function reflectTypeDeclarations(FunctionLike|ClassLike $node): TypeDeclarations
    {
        $phpDoc = $this->parsePhpDoc($node->getDocComment()?->getText());

        if ($phpDoc === null) {
            return new TypeDeclarations();
        }

        return new TypeDeclarations(
            templateNames: array_column($phpDoc->templates(), 'name'),
            aliasNames: [
                ...array_column($phpDoc->typeAliases(), 'alias'),
                ...array_map(
                    static fn(TypeAliasImportTagValueNode $node): string => $node->importedAs ?? $node->importedAlias,
                    $phpDoc->typeAliasImports(),
                ),
            ],
        );
    }

    public function reflect(ClassId|AnonymousClassId|FunctionId $id, TypedMap $data): TypedMap
    {
        if ($id instanceof FunctionId) {
            return $this->reflectFunction($data);
        }

        return $this->reflectClass($data);
    }

    private function reflectClass(TypedMap $data): TypedMap
    {
        $typeReflector = new PhpDocTypeReflector($data[Data::TypeContext]);
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc !== null) {
            if ($phpDoc->hasFinal()) {
                $data = $data->set(Data::AnnotatedFinal, true);
            }

            if ($phpDoc->hasReadonly()) {
                $data = $data->set(Data::AnnotatedReadonly, true);
            }

            $data = $data->set(Data::Templates, $this->reflectTemplates($typeReflector, $phpDoc));
            $data = $this->reflectParent($data, $phpDoc);
            $data = $this->reflectInterfaces($data, $phpDoc);
            $data = $this->reflectUses($data);
        }

        return $data
            ->modify(Data::ClassConstants, fn(array $constants): array => array_map(
                fn(TypedMap $constant): TypedMap => $this->reflectConstant($typeReflector, $constant),
                $constants,
            ))
            ->modify(Data::Properties, fn(array $properties): array => array_map(
                fn(TypedMap $property): TypedMap => $this->reflectProperty($typeReflector, $property),
                $properties,
            ))
            ->modify(Data::Methods, function (array $methods) use ($typeReflector): array {
                $methods = array_map($this->reflectFunction(...), $methods);

                if (isset($methods['__construct'])) {
                    $methods['__construct'] = $this->reflectPromotedProperties($typeReflector, $methods['__construct']);
                }

                return $methods;
            });
    }

    private function reflectParent(TypedMap $data, PhpDoc $phpDoc): TypedMap
    {
        $parent = $data[Data::UnresolvedParent];

        if ($parent === null) {
            return $data;
        }

        $typeContext = $data[Data::TypeContext];
        $typeReflector = new PhpDocTypeReflector($typeContext);

        foreach ($phpDoc->extendedTypes() as $type) {
            if ($parent[0] === $typeContext->resolveClass(NameParser::parse($type->type->name))->toString()) {
                $parent[1] = array_map($typeReflector->reflect(...), $type->genericTypes);
            }
        }

        return $data->set(Data::UnresolvedParent, $parent);
    }

    private function reflectInterfaces(TypedMap $data, PhpDoc $phpDoc): TypedMap
    {
        $interfaces = $data[Data::UnresolvedInterfaces];

        if ($interfaces === []) {
            return $data;
        }

        $typeContext = $data[Data::TypeContext];
        $typeReflector = new PhpDocTypeReflector($typeContext);
        $types = $data[Data::ClassKind] === ClassKind::Interface ? $phpDoc->extendedTypes() : $phpDoc->implementedTypes();

        foreach ($types as $type) {
            $name = $typeContext->resolveClass(NameParser::parse($type->type->name))->toString();

            if (isset($interfaces[$name])) {
                $interfaces[$name] = array_map($typeReflector->reflect(...), $type->genericTypes);
            }
        }

        return $data->set(Data::UnresolvedInterfaces, $interfaces);
    }

    private function reflectUses(TypedMap $data): TypedMap
    {
        $uses = $data[Data::UnresolvedUses];

        if ($uses === []) {
            return $data;
        }

        $typeContext = $data[Data::TypeContext];
        $typeReflector = new PhpDocTypeReflector($typeContext);

        foreach ($data[Data::UsePhpDocs] as $phpDocText) {
            $phpDoc = $this->parsePhpDoc($phpDocText);

            foreach ($phpDoc->usedTypes() as $type) {
                $name = $typeContext->resolveClass(NameParser::parse($type->type->name))->toString();

                if (isset($uses[$name])) {
                    $uses[$name] = array_map($typeReflector->reflect(...), $type->genericTypes);
                }
            }
        }

        return $data->set(Data::UnresolvedUses, $uses);
    }

    /**
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectTemplates(PhpDocTypeReflector $typeReflector, PhpDoc $phpDoc): array
    {
        $templates = [];

        foreach ($phpDoc->templates() as $index => $template) {
            $templates[$template->name] = (new TypedMap())
                ->set(Data::Index, $index)
                ->set(Data::Constraint, $typeReflector->reflect($template->bound) ?? types::mixed)
                ->set(Data::Variance, PhpDoc::templateTagVariance($template));
        }

        return $templates;
    }

    private function reflectFunction(TypedMap $data): TypedMap
    {
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc === null) {
            return $data;
        }

        $typeReflector = new PhpDocTypeReflector($data[Data::TypeContext]);

        $data = $data
            ->set(Data::Templates, $this->reflectTemplates($typeReflector, $phpDoc))
            ->modify(Data::Parameters, function (array $parameters) use ($typeReflector, $phpDoc): array {
                $types = $phpDoc->paramTypes();

                foreach ($types as $name => $type) {
                    if (isset($parameters[$name])) {
                        $parameters[$name] = $this->setAnnotatedType($typeReflector, $type, $parameters[$name]);
                    }
                }

                return $parameters;
            });

        $returnType = $phpDoc->returnType();

        if ($returnType !== null) {
            $data = $this->setAnnotatedType($typeReflector, $returnType, $data);
        }

        $throwsTypes = $phpDoc->throwsTypes();

        if ($throwsTypes !== []) {
            $data = $data->set(Data::ThrowsType, types::union(...array_map($typeReflector->reflect(...), $throwsTypes)));
        }

        return $data;
    }

    private function reflectConstant(PhpDocTypeReflector $typeReflector, TypedMap $data): TypedMap
    {
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc === null) {
            return $data;
        }

        if ($phpDoc->hasFinal()) {
            $data = $data->set(Data::AnnotatedFinal, true);
        }

        $type = $phpDoc->varType();

        if ($type !== null) {
            $data = $this->setAnnotatedType($typeReflector, $type, $data);
        }

        return $data;
    }

    private function reflectProperty(PhpDocTypeReflector $typeReflector, TypedMap $data): TypedMap
    {
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc === null) {
            return $data;
        }

        if ($phpDoc->hasReadonly()) {
            $data = $data->set(Data::AnnotatedReadonly, true);
        }

        $type = $phpDoc->varType();

        if ($type !== null) {
            $data = $this->setAnnotatedType($typeReflector, $type, $data);
        }

        return $data;
    }

    private function reflectPromotedProperties(PhpDocTypeReflector $typeReflector, TypedMap $data): TypedMap
    {
        return $data->modify(Data::Parameters, fn(array $parameters): array => array_map(
            function (TypedMap $parameter) use ($typeReflector): TypedMap {
                if (!$parameter[Data::Promoted]) {
                    return $parameter;
                }

                return $this->reflectProperty($typeReflector, $parameter);
            },
            $parameters,
        ));
    }

    private function setAnnotatedType(PhpDocTypeReflector $typeReflector, TypeNode $node, TypedMap $data): TypedMap
    {
        return $data->modify(Data::Type, static fn(TypeData $type): TypeData => $type->withAnnotated($typeReflector->reflect($node)));
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
