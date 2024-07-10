<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ResolveAttributesRepeated implements ReflectionHook
{
    public function reflect(FunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data): TypedMap
    {
        if ($id instanceof FunctionId) {
            return $this->resolveAttributesRepeated($data);
        }

        return $this
            ->resolveAttributesRepeated($data)
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
