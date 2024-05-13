<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ResolveClassInheritance;

use Typhoon\DeclarationId\MethodId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Visibility;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\parameterId;

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

    private readonly TypeInheritanceResolver $type;

    public function __construct()
    {
        $this->type = new TypeInheritanceResolver();
    }

    public function setOwn(TypedMap $data): void
    {
        $methodId = $data[Data::DeclarationId()];
        \assert($methodId instanceof MethodId);

        $this->data = $data;

        foreach ($data[Data::Parameters()] ?? [] as $name => $parameter) {
            ($this->parameters[$name] = new BasicInheritanceResolver())->setOwn($parameter->set(Data::DeclarationId(), parameterId($methodId, $name)));
        }

        $this->type->setOwn($data);
    }

    public function addUsed(TypedMap $data, TypeProcessor $typeProcessor): void
    {
        if ($this->data !== null) {
            $usedParameters = array_values($data[Data::Parameters()]);

            foreach (array_values($this->parameters) as $index => $parameter) {
                if (isset($usedParameters[$index])) {
                    $parameter->addInherited($usedParameters[$index], $typeProcessor);
                }
            }

            $this->type->addInherited($data, $typeProcessor);

            return;
        }

        $this->data = $data;

        foreach ($data[Data::Parameters()] ?? [] as $name => $parameter) {
            ($this->parameters[$name] = new BasicInheritanceResolver())->addInherited($parameter, $typeProcessor);
        }

        $this->type->addInherited($data, $typeProcessor);
    }

    public function addInherited(TypedMap $data, TypeProcessor $typeProcessor): void
    {
        if ($data[Data::Visibility()] === Visibility::Private) {
            return;
        }

        if ($this->data !== null) {
            $usedParameters = array_values($data[Data::Parameters()]);

            foreach (array_values($this->parameters) as $index => $parameter) {
                if (isset($usedParameters[$index])) {
                    $parameter->addInherited($usedParameters[$index], $typeProcessor);
                }
            }

            $this->type->addInherited($data, $typeProcessor);

            return;
        }

        $this->data = $data;

        foreach ($data[Data::Parameters()] as $name => $parameter) {
            ($this->parameters[$name] = new BasicInheritanceResolver())->addInherited($parameter, $typeProcessor);
        }

        $this->type->addInherited($data, $typeProcessor);
    }

    public function resolve(): ?TypedMap
    {
        if ($this->data === null) {
            return null;
        }

        return $this
            ->data
            ->set(Data::Parameters(), array_filter(array_map(
                static fn(BasicInheritanceResolver $parameter): ?TypedMap => $parameter->resolve(),
                $this->parameters,
            )))
            ->set(Data::ResolvedType(), $this->type->resolve());
    }
}
