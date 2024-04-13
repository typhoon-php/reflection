<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ResolveAttributesRepeated implements ReflectionHook
{
    public function reflect(FunctionId|ClassId|AnonymousClassId $id, TypedMap $data): TypedMap
    {
        if ($id instanceof FunctionId) {
            return $this->resolveAttributesRepeated($data);
        }

        $data = $this->resolveAttributesRepeated($data);

        if (isset($data[Data::ClassConstants()])) {
            $data = $data->with(Data::ClassConstants(), array_map(
                $this->resolveAttributesRepeated(...),
                $data[Data::ClassConstants()],
            ));
        }

        if (isset($data[Data::Properties()])) {
            $data = $data->with(Data::Properties(), array_map(
                $this->resolveAttributesRepeated(...),
                $data[Data::Properties()],
            ));
        }

        if (isset($data[Data::Methods()])) {
            $data = $data->with(Data::Methods(), array_map(
                function (TypedMap $data): TypedMap {
                    $data = $this->resolveAttributesRepeated($data);

                    if (isset($data[Data::Parameters()])) {
                        $data = $data->with(Data::Parameters(), array_map(
                            $this->resolveAttributesRepeated(...),
                            $data[Data::Parameters()],
                        ));
                    }

                    return $data;
                },
                $data[Data::Methods()],
            ));
        }

        return $data;
    }

    private function resolveAttributesRepeated(TypedMap $data): TypedMap
    {
        $attributes = $data[Data::Attributes()] ?? [];

        if ($attributes === []) {
            return $data;
        }

        $repeated = [];

        foreach ($attributes as $attribute) {
            $className = $attribute[Data::AttributeClass()];
            $repeated[$className] = isset($repeated[$className]);
        }

        return $data->with(Data::Attributes(), array_map(
            static fn(TypedMap $attribute): TypedMap => $attribute->with(
                Data::Repeated(),
                $repeated[$attribute[Data::AttributeClass()]],
            ),
            $attributes,
        ));
    }
}
