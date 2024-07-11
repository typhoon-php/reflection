<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ResolveClassInheritance;

use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\TypeData;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Reflection\Internal\Visibility;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class MethodInheritanceResolver
{
    private ?TypedMap $data = null;

    /**
     * @var array<non-empty-string, BasicInheritanceResolver>
     */
    private array $parameters = [];

    private readonly TypeInheritanceResolver $returnType;

    private readonly TypeInheritanceResolver $throwsType;

    public function __construct()
    {
        $this->returnType = new TypeInheritanceResolver();
        $this->throwsType = new TypeInheritanceResolver();
    }

    public function setOwn(TypedMap $data): void
    {
        $this->data = $data;

        foreach ($data[Data::Parameters] as $name => $parameter) {
            ($this->parameters[$name] = new BasicInheritanceResolver())->setOwn($parameter);
        }

        $this->returnType->setOwn($data[Data::Type]);
        $this->throwsType->setOwn(new TypeData(annotated: $data[Data::ThrowsType]));
    }

    public function addUsed(TypedMap $data, TypeProcessor $typeProcessor): void
    {
        if ($this->data !== null) {
            $usedParameters = array_values($data[Data::Parameters]);

            foreach (array_values($this->parameters) as $index => $parameter) {
                if (isset($usedParameters[$index])) {
                    $parameter->addInherited($usedParameters[$index], $typeProcessor);
                }
            }

            $this->returnType->addInherited($data[Data::Type], $typeProcessor);

            return;
        }

        $this->data = $data;

        foreach ($data[Data::Parameters] as $name => $parameter) {
            ($this->parameters[$name] = new BasicInheritanceResolver())->addInherited($parameter, $typeProcessor);
        }

        $this->returnType->addInherited($data[Data::Type], $typeProcessor);
        $this->throwsType->addInherited(new TypeData(annotated: $data[Data::ThrowsType]), $typeProcessor);
    }

    public function addInherited(TypedMap $data, TypeProcessor $typeProcessor): void
    {
        if ($data[Data::Visibility] === Visibility::Private) {
            return;
        }

        if ($this->data !== null) {
            $usedParameters = array_values($data[Data::Parameters]);

            foreach (array_values($this->parameters) as $index => $parameter) {
                if (isset($usedParameters[$index])) {
                    $parameter->addInherited($usedParameters[$index], $typeProcessor);
                }
            }

            $this->returnType->addInherited($data[Data::Type], $typeProcessor);
            $this->throwsType->addInherited(new TypeData(annotated: $data[Data::ThrowsType]), $typeProcessor);

            return;
        }

        $this->data = $data;

        foreach ($data[Data::Parameters] as $name => $parameter) {
            ($this->parameters[$name] = new BasicInheritanceResolver())->addInherited($parameter, $typeProcessor);
        }

        $this->returnType->addInherited($data[Data::Type], $typeProcessor);
        $this->throwsType->addInherited(new TypeData(annotated: $data[Data::ThrowsType]), $typeProcessor);
    }

    public function resolve(): ?TypedMap
    {
        return $this
            ->data
            ?->set(Data::Parameters, array_filter(array_map(
                static fn(BasicInheritanceResolver $parameter): ?TypedMap => $parameter->resolve(),
                $this->parameters,
            )))
            ->set(Data::Type, $this->returnType->resolve())
            ->set(Data::ThrowsType, $this->throwsType->resolve()->annotated);
    }
}
