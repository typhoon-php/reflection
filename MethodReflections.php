<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\methodId;

/**
 * @api
 * @extends Reflections<string, MethodReflection>
 */
final class MethodReflections extends Reflections
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
        return $this->filter(static fn(MethodReflection $reflection): bool => $reflection->isPublic());
    }

    public function protected(): self
    {
        return $this->filter(static fn(MethodReflection $reflection): bool => $reflection->isProtected());
    }

    public function private(): self
    {
        return $this->filter(static fn(MethodReflection $reflection): bool => $reflection->isPrivate());
    }

    public function static(): self
    {
        return $this->filter(static fn(MethodReflection $reflection): bool => $reflection->isStatic());
    }

    public function nonStatic(): self
    {
        return $this->filter(static fn(MethodReflection $reflection): bool => !$reflection->isStatic());
    }

    /**
     * @param ?int-mask-of<\ReflectionMethod::IS_*> $filter
     * @return array<non-empty-string, \ReflectionMethod>
     */
    public function toNative(?int $filter = null): array
    {
        $filter ??= 0;
        $methods = [];

        foreach ($this as $name => $method) {
            $native = $method->toNative();

            if ($filter > 0 && ($native->getModifiers() & $filter) === 0) {
                continue;
            }

            $methods[$name] = $native;
        }

        return $methods;
    }

    protected function load(string $name, TypedMap $data): Reflection
    {
        return new MethodReflection(methodId($this->classId, $name), $data, $this->reflector);
    }
}
