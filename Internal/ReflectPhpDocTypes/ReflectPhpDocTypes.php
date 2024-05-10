<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ReflectPhpDocTypes;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\ReflectionHook;
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
        $phpDoc = $this->parsePhpDoc($data);

        if ($phpDoc !== null) {
            if ($phpDoc->hasFinal()) {
                $data = $data->with(Data::AnnotatedFinal(), true);
            }

            if ($phpDoc->hasReadonly()) {
                $data = $data->with(Data::AnnotatedReadonly(), true);
            }
        }

        $typeReflector = new ContextualPhpDocTypeReflector($data[Data::TypeContext()]);

        if (isset($data[Data::Properties()])) {
            $data = $data->with(Data::Properties(), array_map(
                fn(TypedMap $property): TypedMap => $this->reflectProperty($typeReflector, $property),
                $data[Data::Properties()],
            ));
        }

        if (isset($data[Data::Methods()])) {
            $data = $data->with(Data::Methods(), array_map($this->reflectFunction(...), $data[Data::Methods()]));
        }

        return $data;
    }

    private function reflectFunction(TypedMap $data): TypedMap
    {
        $phpDoc = $this->parsePhpDoc($data);

        if ($phpDoc === null) {
            return $data;
        }

        $typeReflector = new ContextualPhpDocTypeReflector($data[Data::TypeContext()]);

        $paramTypes = $phpDoc->paramTypes();
        $parameters = $data[Data::Parameters()];

        if ($parameters !== []) {
            foreach ($parameters as $name => &$parameter) {
                if (isset($paramTypes[$name])) {
                    $parameter = $parameter->with(Data::AnnotatedType(), $typeReflector->reflect($paramTypes[$name]));
                }
            }

            $data = $data->with(Data::Parameters(), $parameters);
        }

        $returnType = $phpDoc->returnType();

        if ($returnType !== null) {
            $data = $data->with(Data::AnnotatedType(), $typeReflector->reflect($returnType));
        }

        $throwsTypes = $phpDoc->throwsTypes();

        if ($throwsTypes !== []) {
            $data = $data->with(Data::ThrowsType(), types::union(...array_map($typeReflector->reflect(...), $throwsTypes)));
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
            $data = $data->with(Data::AnnotatedReadonly(), true);
        }

        $type = $phpDoc->varType();

        if ($type !== null) {
            $data = $data->with(Data::AnnotatedType(), $typeReflector->reflect($type));
        }

        return $data;
    }

    private function parsePhpDoc(TypedMap $data): ?PhpDoc
    {
        $phpDocText = $data[Data::PhpDoc()] ?? null;

        if ($phpDocText === null) {
            return null;
        }

        return $this->parser->parse($phpDocText);
    }
}
