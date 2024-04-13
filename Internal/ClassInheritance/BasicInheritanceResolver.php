<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ClassInheritance;

use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Visibility;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\ClassInheritance
 */
final class BasicInheritanceResolver
{
    private ?TypedMap $data = null;

    private readonly TypeInheritanceResolver $type;

    public function __construct()
    {
        $this->type = new TypeInheritanceResolver();
    }

    public function setOwn(TypedMap $data): void
    {
        $this->data = $data;
        $this->type->setOwn($data);
    }

    public function addUsed(TypedMap $data, TypeProcessor $typeProcessor): void
    {
        $this->data ??= $data;
        $this->type->addInherited($data, $typeProcessor);
    }

    public function addInherited(TypedMap $data, TypeProcessor $typeProcessor): void
    {
        if ($data[Data::Visibility()] === Visibility::Private) {
            return;
        }

        $this->data ??= $data;
        $this->type->addInherited($data, $typeProcessor);
    }

    public function resolve(): ?TypedMap
    {
        return $this->data?->with(Data::ResolvedType(), $this->type->resolve());
    }
}
