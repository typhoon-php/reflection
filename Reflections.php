<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\TypedMap\TypedMap;

/**
 * @api
 * @template TArrayAccessKey of int|string
 * @template TReflection of Reflection
 * @implements \ArrayAccess<TArrayAccessKey, TReflection>
 * @implements \IteratorAggregate<non-empty-string, TReflection>
 */
abstract class Reflections implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @var array<non-empty-string, TypedMap|TReflection>
     */
    private array $reflections;

    /**
     * @param array<non-empty-string, TypedMap> $data
     */
    protected function __construct(array $data)
    {
        $this->reflections = $data;
    }

    /**
     * @return list<non-empty-string>
     */
    final public function names(): array
    {
        return array_keys($this->reflections);
    }

    /**
     * @return list<TReflection>
     */
    final public function toList(): array
    {
        return iterator_to_array($this, preserve_keys: false);
    }

    /**
     * @return array<non-empty-string, TReflection>
     */
    final public function toArray(): array
    {
        return iterator_to_array($this);
    }

    final public function isEmpty(): bool
    {
        return $this->reflections === [];
    }

    /**
     * @template T
     * @param callable(TReflection): T $mapper
     * @return array<non-empty-string, T>
     */
    final public function map(callable $mapper): array
    {
        $values = [];

        foreach ($this as $name => $reflection) {
            $values[$name] = $mapper($reflection);
        }

        return $values;
    }

    /**
     * @param callable(TReflection): bool $filter
     * @return static<TArrayAccessKey, TReflection>
     */
    final public function filter(callable $filter): static
    {
        $copy = clone $this;

        foreach ($copy as $name => $reflection) {
            if (!$filter($reflection)) {
                unset($copy->reflections[$name]);
            }
        }

        return $copy;
    }

    /**
     * @param TArrayAccessKey $offset
     */
    final public function offsetExists(mixed $offset): bool
    {
        if (\is_int($offset)) {
            return $offset >= 0 && $offset < $this->count();
        }

        return isset($this->reflections[$offset]);
    }

    /**
     * @param TArrayAccessKey $offset
     * @return TReflection
     */
    final public function offsetGet(mixed $offset): mixed
    {
        if (\is_int($offset)) {
            $name = $this->names()[$offset] ?? null;

            if ($name === null) {
                throw new \LogicException();
            }
        } else {
            $name = $offset;
        }

        $reflection = $this->reflections[$name] ?? throw new \LogicException();

        if ($reflection instanceof TypedMap) {
            /** @var non-empty-string $name */
            return $this->reflections[$name] = $this->load($name, $reflection);
        }

        return $reflection;
    }

    final public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException();
    }

    final public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException();
    }

    /**
     * @return \Generator<non-empty-string, TReflection>
     */
    final public function getIterator(): \Generator
    {
        foreach ($this->reflections as $name => $reflection) {
            if ($reflection instanceof TypedMap) {
                $reflection = $this->reflections[$name] = $this->load($name, $reflection);
            }

            yield $name => $reflection;
        }
    }

    /**
     * @return non-negative-int
     */
    final public function count(): int
    {
        return \count($this->reflections);
    }

    /**
     * @param non-empty-string $name
     * @return TReflection
     */
    abstract protected function load(string $name, TypedMap $data): Reflection;
}
