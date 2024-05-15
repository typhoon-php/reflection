<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\classConstantId;

/**
 * @api
 * @extends Reflections<string, ClassConstantReflection>
 */
final class ClassConstantReflections extends Reflections
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
        return $this->filter(static fn(ClassConstantReflection $reflection): bool => $reflection->isPublic());
    }

    public function protected(): self
    {
        return $this->filter(static fn(ClassConstantReflection $reflection): bool => $reflection->isProtected());
    }

    public function private(): self
    {
        return $this->filter(static fn(ClassConstantReflection $reflection): bool => $reflection->isPrivate());
    }

    protected function load(string $name, TypedMap $data): Reflection
    {
        return new ClassConstantReflection(classConstantId($this->classId, $name), $data, $this->reflector);
    }
}
