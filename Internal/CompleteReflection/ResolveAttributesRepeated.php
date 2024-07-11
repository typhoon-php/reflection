<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\ClassReflectionHook;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\DataReflector;
use Typhoon\Reflection\Internal\FunctionReflectionHook;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ResolveAttributesRepeated implements FunctionReflectionHook, ClassReflectionHook
{
    public function process(NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, DataReflector $reflector): TypedMap
    {
        $data = $this->resolveAttributesRepeated($data);

        if ($id instanceof NamedFunctionId || $id instanceof AnonymousFunctionId) {
            return $data;
        }

        return $data
            ->modifyIfSet(Data::ClassConstants, fn(array $constants): array => array_map(
                $this->resolveAttributesRepeated(...),
                $constants,
            ))
            ->modifyIfSet(Data::Properties, fn(array $properties): array => array_map(
                $this->resolveAttributesRepeated(...),
                $properties,
            ))
            ->modifyIfSet(Data::Methods, fn(array $methods): array => array_map(
                fn(TypedMap $data): TypedMap => $this
                    ->resolveAttributesRepeated($data)
                    ->modifyIfSet(Data::Parameters, fn(array $parameters): array => array_map(
                        $this->resolveAttributesRepeated(...),
                        $parameters,
                    )),
                $methods,
            ));
    }

    private function resolveAttributesRepeated(TypedMap $data): TypedMap
    {
        $attributes = $data[Data::Attributes];

        if ($attributes === []) {
            return $data;
        }

        $repeated = [];

        foreach ($attributes as $attribute) {
            $class = $attribute[Data::AttributeClassName];
            $repeated[$class] = isset($repeated[$class]);
        }

        return $data->set(Data::Attributes, array_map(
            static fn(TypedMap $attribute): TypedMap => $attribute->set(
                Data::AttributeRepeated,
                $repeated[$attribute[Data::AttributeClassName]],
            ),
            $attributes,
        ));
    }
}
