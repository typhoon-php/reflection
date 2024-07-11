<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ReflectPhpDocTypes;

use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasImportTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\ClassKind;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\DataReflector;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\Reflection\Internal\TypeContext\AnnotatedTypesDriver;
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

    public function reflect(NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, DataReflector $reflector): TypedMap
    {
        if ($id instanceof NamedFunctionId || $id instanceof AnonymousFunctionId) {
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
            $data = $data->set(Data::Aliases, $this->reflectAliases($typeReflector, $phpDoc));
            $data = $this->reflectParent($typeReflector, $data, $phpDoc);
            $data = $this->reflectInterfaces($typeReflector, $data, $phpDoc);
            $data = $this->reflectUses($typeReflector, $data);
        }

        return $data
            ->modifyIfSet(Data::ClassConstants, fn(array $constants): array => array_map(
                fn(TypedMap $constant): TypedMap => $this->reflectConstant($typeReflector, $constant),
                $constants,
            ))
            ->modifyIfSet(Data::Properties, fn(array $properties): array => array_map(
                fn(TypedMap $property): TypedMap => $this->reflectProperty($typeReflector, $property),
                $properties,
            ))
            ->modifyIfSet(Data::Methods, function (array $methods) use ($typeReflector): array {
                $methods = array_map($this->reflectFunction(...), $methods);

                if (isset($methods['__construct'])) {
                    $methods['__construct'] = $this->reflectPromotedProperties($typeReflector, $methods['__construct']);
                }

                return $methods;
            });
    }

    private function reflectParent(PhpDocTypeReflector $typeReflector, TypedMap $data, PhpDoc $phpDoc): TypedMap
    {
        $parent = $data[Data::UnresolvedParent];

        if ($parent === null) {
            return $data;
        }

        foreach ($phpDoc->extendedTypes() as $type) {
            if ($parent[0] === $typeReflector->resolveClass($type->type)) {
                $parent[1] = array_map($typeReflector->reflectType(...), $type->genericTypes);
            }
        }

        return $data->set(Data::UnresolvedParent, $parent);
    }

    private function reflectInterfaces(PhpDocTypeReflector $typeReflector, TypedMap $data, PhpDoc $phpDoc): TypedMap
    {
        $interfaces = $data[Data::UnresolvedInterfaces];

        if ($interfaces === []) {
            return $data;
        }

        $types = $data[Data::ClassKind] === ClassKind::Interface ? $phpDoc->extendedTypes() : $phpDoc->implementedTypes();

        foreach ($types as $type) {
            $name = $typeReflector->resolveClass($type->type);

            if (isset($interfaces[$name])) {
                $interfaces[$name] = array_map($typeReflector->reflectType(...), $type->genericTypes);
            }
        }

        return $data->set(Data::UnresolvedInterfaces, $interfaces);
    }

    private function reflectUses(PhpDocTypeReflector $typeReflector, TypedMap $data): TypedMap
    {
        $uses = $data[Data::UnresolvedTraits];

        if ($uses === []) {
            return $data;
        }

        foreach ($data[Data::UsePhpDocs] as $phpDocText) {
            $phpDoc = $this->parsePhpDoc($phpDocText);

            foreach ($phpDoc->usedTypes() as $type) {
                $name = $typeReflector->resolveClass($type->type);

                if (isset($uses[$name])) {
                    $uses[$name] = array_map($typeReflector->reflectType(...), $type->genericTypes);
                }
            }
        }

        return $data->set(Data::UnresolvedTraits, $uses);
    }

    /**
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectAliases(PhpDocTypeReflector $typeReflector, PhpDoc $phpDoc): array
    {
        $aliases = [];

        foreach ($phpDoc->typeAliases() as $alias) {
            $aliases[$alias->alias] = (new TypedMap())->set(Data::AliasType, $typeReflector->reflectType($alias->type));
        }

        foreach ($phpDoc->typeAliasImports() as $aliasImport) {
            $aliases[$aliasImport->importedAs ?? $aliasImport->importedAlias] = (new TypedMap())->set(
                Data::AliasType,
                types::alias(Id::alias($typeReflector->resolveClass($aliasImport->importedFrom), $aliasImport->importedAlias)),
            );
        }

        return $aliases;
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
                ->set(Data::Constraint, $typeReflector->reflectType($template->bound) ?? types::mixed)
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
            ->modifyIfSet(Data::Parameters, function (array $parameters) use ($typeReflector, $phpDoc): array {
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
            $data = $data->set(Data::ThrowsType, types::union(...array_map($typeReflector->reflectType(...), $throwsTypes)));
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
        return $data->modifyIfSet(Data::Parameters, fn(array $parameters): array => array_map(
            fn(TypedMap $parameter): TypedMap => $parameter[Data::Promoted] ? $this->reflectProperty($typeReflector, $parameter) : $parameter,
            $parameters,
        ));
    }

    private function setAnnotatedType(PhpDocTypeReflector $typeReflector, TypeNode $node, TypedMap $data): TypedMap
    {
        return $data->modify(
            Data::Type,
            static fn(TypeData $type): TypeData => $type->withAnnotated($typeReflector->reflectType($node)),
        );
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
