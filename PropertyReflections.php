<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\propertyId;

/**
 * @api
 * @extends Reflections<string, PropertyReflection>
 */
final class PropertyReflections extends Reflections
{
    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param array<non-empty-string, TypedMap> $data
     */
    public function __construct(
        private readonly ClassId|AnonymousClassId $classId,
        array $data,
        private readonly Reflector $reflector,
    ) {
        parent::__construct($data);
    }

    public function public(): self
    {
        return $this->filter(static fn(PropertyReflection $reflection): bool => $reflection->isPublic());
    }

    public function protected(): self
    {
        return $this->filter(static fn(PropertyReflection $reflection): bool => $reflection->isProtected());
    }

    public function private(): self
    {
        return $this->filter(static fn(PropertyReflection $reflection): bool => $reflection->isPrivate());
    }

    public function static(): self
    {
        return $this->filter(static fn(PropertyReflection $reflection): bool => $reflection->isStatic());
    }

    public function nonStatic(): self
    {
        return $this->filter(static fn(PropertyReflection $reflection): bool => !$reflection->isStatic());
    }

    public function readonly(): self
    {
        return $this->filter(static fn(PropertyReflection $reflection): bool => $reflection->isReadonly());
    }

    public function nonReadonly(): self
    {
        return $this->filter(static fn(PropertyReflection $reflection): bool => !$reflection->isReadonly());
    }

    protected function load(string $name, TypedMap $data): Reflection
    {
        return new PropertyReflection(propertyId($this->classId, $name), $data, $this->reflector);
    }
}
