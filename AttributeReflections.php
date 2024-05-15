<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\DeclarationId;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 * @implements \IteratorAggregate<non-negative-int, AttributeReflection>
 */
final class AttributeReflections implements \IteratorAggregate, \Countable
{
    /**
     * @var list<TypedMap|AttributeReflection>
     */
    private array $reflections;

    /**
     * @param list<TypedMap> $data
     */
    public function __construct(
        public readonly DeclarationId $targetId,
        array $data,
        private readonly Reflector $reflector,
    ) {
        $this->reflections = $data;
    }

    /**
     * @return list<AttributeReflection>
     */
    public function toList(): array
    {
        return iterator_to_array($this, preserve_keys: false);
    }

    public function isEmpty(): bool
    {
        return $this->reflections === [];
    }

    /**
     * @template T
     * @param callable(AttributeReflection): T $mapper
     * @return list<T>
     */
    public function map(callable $mapper): array
    {
        $values = [];

        foreach ($this as $reflection) {
            $values[] = $mapper($reflection);
        }

        return $values;
    }

    /**
     * @param callable(AttributeReflection): bool $filter
     */
    public function filter(callable $filter): self
    {
        $copy = clone $this;
        $copy->reflections = [];

        foreach ($this as $reflection) {
            if ($filter($reflection)) {
                $copy->reflections[] = $reflection;
            }
        }

        return $copy;
    }

    public function class(string $class): self
    {
        return $this->filter(static fn(AttributeReflection $reflection): bool => $reflection->className() === $class);
    }

    public function instanceOf(string $class): self
    {
        return $this->filter(static fn(AttributeReflection $reflection): bool => $reflection->class()->isInstanceOf($class));
    }

    public function first(): ?AttributeReflection
    {
        foreach ($this as $reflection) {
            return $reflection;
        }

        return null;
    }

    /**
     * @return \Generator<non-negative-int, AttributeReflection>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->reflections as $name => $reflection) {
            if ($reflection instanceof TypedMap) {
                $reflection = $this->reflections[$name] = new AttributeReflection($this->targetId, $reflection, $this->reflector);
            }

            yield $name => $reflection;
        }
    }

    /**
     * @return non-negative-int
     */
    public function count(): int
    {
        return \count($this->reflections);
    }

    /**
     * @return list<\ReflectionAttribute>
     */
    public function toNative(?string $name = null, int $flags = 0): array
    {
        $attributes = $this;

        if ($name !== null) {
            if ($flags & \ReflectionAttribute::IS_INSTANCEOF) {
                $attributes = $attributes->instanceOf($name);
            } else {
                $attributes = $attributes->class($name);
            }
        }

        return $attributes->map(static fn(AttributeReflection $attribute): \ReflectionAttribute => $attribute->toNative());
    }
}
