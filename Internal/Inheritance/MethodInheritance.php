<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Inheritance;

use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\Data\TypeData;
use Typhoon\Reflection\Internal\Data\Visibility;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\Inheritance
 */
final class MethodInheritance
{
    private ?TypedMap $data = null;

    /**
     * @var array<non-empty-string, PropertyInheritance>
     */
    private array $parameters = [];

    private readonly TypeInheritance $returnType;

    private readonly TypeInheritance $throwsType;

    public function __construct()
    {
        $this->returnType = new TypeInheritance();
        $this->throwsType = new TypeInheritance();
    }

    public function applyOwn(TypedMap $data): void
    {
        $this->data = $data;

        foreach ($data[Data::Parameters] as $name => $parameter) {
            ($this->parameters[$name] = new PropertyInheritance())->applyOwn($parameter);
        }

        $this->returnType->applyOwn($data[Data::Type]);
        $this->throwsType->applyOwn(new TypeData(annotated: $data[Data::ThrowsType]));
    }

    public function applyUsed(TypedMap $data, TypeResolvers $typeResolvers): void
    {
        if ($this->data !== null) {
            $usedParameters = array_values($data[Data::Parameters]);

            foreach (array_values($this->parameters) as $index => $parameter) {
                if (isset($usedParameters[$index])) {
                    $parameter->applyInherited($usedParameters[$index], $typeResolvers);
                }
            }

            $this->returnType->applyInherited($data[Data::Type], $typeResolvers);

            return;
        }

        $this->data = $data;

        foreach ($data[Data::Parameters] as $name => $parameter) {
            ($this->parameters[$name] = new PropertyInheritance())->applyInherited($parameter, $typeResolvers);
        }

        $this->returnType->applyInherited($data[Data::Type], $typeResolvers);
        $this->throwsType->applyInherited(new TypeData(annotated: $data[Data::ThrowsType]), $typeResolvers);
    }

    public function applyInherited(TypedMap $data, TypeResolvers $typeResolvers): void
    {
        if ($data[Data::Visibility] === Visibility::Private) {
            return;
        }

        if ($this->data !== null) {
            $usedParameters = array_values($data[Data::Parameters]);

            foreach (array_values($this->parameters) as $index => $parameter) {
                if (isset($usedParameters[$index])) {
                    $parameter->applyInherited($usedParameters[$index], $typeResolvers);
                }
            }

            $this->returnType->applyInherited($data[Data::Type], $typeResolvers);
            $this->throwsType->applyInherited(new TypeData(annotated: $data[Data::ThrowsType]), $typeResolvers);

            return;
        }

        $this->data = $data;

        foreach ($data[Data::Parameters] as $name => $parameter) {
            ($this->parameters[$name] = new PropertyInheritance())->applyInherited($parameter, $typeResolvers);
        }

        $this->returnType->applyInherited($data[Data::Type], $typeResolvers);
        $this->throwsType->applyInherited(new TypeData(annotated: $data[Data::ThrowsType]), $typeResolvers);
    }

    public function build(): ?TypedMap
    {
        return $this
            ->data
            ?->set(Data::Parameters, array_filter(array_map(
                static fn(PropertyInheritance $parameter): ?TypedMap => $parameter->build(),
                $this->parameters,
            )))
            ->set(Data::Type, $this->returnType->build())
            ->set(Data::ThrowsType, $this->throwsType->build()->annotated);
    }
}
