<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ReflectPhpDocTypes;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\Reflection\Internal\TypeData;
use Typhoon\Type\types;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ReflectPhpDocTypes implements ReflectionHook
{
    public function __construct(
        private readonly PhpDocParser $parser = new PhpDocParser(),
    ) {}

    public function reflect(ClassId|AnonymousClassId|FunctionId $id, TypedMap $data): TypedMap
    {
        if ($id instanceof FunctionId) {
            return $this->reflectFunction($data);
        }

        return $this->reflectClass($data);
    }

    private function reflectClass(TypedMap $data): TypedMap
    {
        $typeReflector = new ContextualPhpDocTypeReflector($data[Data::TypeContext]);
        $phpDoc = $this->parsePhpDoc($data);

        if ($phpDoc !== null) {
            if ($phpDoc->hasFinal()) {
                $data = $data->set(Data::AnnotatedFinal, true);
            }

            if ($phpDoc->hasReadonly()) {
                $data = $data->set(Data::AnnotatedReadonly, true);
            }

            $data = $data->set(Data::Templates, $this->reflectTemplates($typeReflector, $phpDoc));
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

    /**
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectTemplates(ContextualPhpDocTypeReflector $typeReflector, PhpDoc $phpDoc): array
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
        $phpDoc = $this->parsePhpDoc($data);

        if ($phpDoc === null) {
            return $data;
        }

        $typeReflector = new ContextualPhpDocTypeReflector($data[Data::TypeContext]);

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

    private function reflectConstant(ContextualPhpDocTypeReflector $typeReflector, TypedMap $data): TypedMap
    {
        $phpDoc = $this->parsePhpDoc($data);

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

    private function reflectProperty(ContextualPhpDocTypeReflector $typeReflector, TypedMap $data): TypedMap
    {
        $phpDoc = $this->parsePhpDoc($data);

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

    private function reflectPromotedProperties(ContextualPhpDocTypeReflector $typeReflector, TypedMap $data): TypedMap
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

    private function setAnnotatedType(ContextualPhpDocTypeReflector $typeReflector, TypeNode $node, TypedMap $data): TypedMap
    {
        return $data->modify(Data::Type, static fn(TypeData $type): TypeData => $type->withAnnotated($typeReflector->reflect($node)));
    }

    private function parsePhpDoc(TypedMap $data): ?PhpDoc
    {
        $phpDocText = $data[Data::PhpDoc];

        if ($phpDocText === null) {
            return null;
        }

        return $this->parser->parse($phpDocText);
    }
}
