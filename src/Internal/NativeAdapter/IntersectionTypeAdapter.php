<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class IntersectionTypeAdapter extends \ReflectionIntersectionType
{
    /**
     * @param non-empty-list<\ReflectionNamedType> $types
     */
    public function __construct(
        private readonly array $types,
    ) {}

    public function allowsNull(): bool
    {
        return false;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function __toString(): string
    {
        return implode('&', $this->types);
    }
}
