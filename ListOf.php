<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 * @template-covariant T
 * @implements \ArrayAccess<int, T>
 * @implements \IteratorAggregate<non-negative-int, T>
 * @psalm-suppress InvalidTemplateParam
 */
final class ListOf implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param list<T> $values
     */
    public function __construct(
        private readonly array $values,
    ) {}

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->values[$offset] ?? throw new \RuntimeException();
    }

    /**
     * @template TNew
     * @param callable(T, non-negative-int): TNew $mapper
     * @return self<TNew>
     */
    public function map(callable $mapper): self
    {
        $values = [];

        foreach ($this->values as $index => $value) {
            $values[] = $mapper($value, $index);
        }

        return new self($values);
    }

    /**
     * @param callable(T, non-negative-int): bool $filter
     * @return self<T>
     */
    public function filter(callable $filter): self
    {
        $values = [];

        foreach ($this->values as $index => $value) {
            if ($filter($value, $index)) {
                $values[] = $value;
            }
        }

        return new self($values);
    }

    /**
     * @return list<T>
     */
    public function toList(): array
    {
        return $this->values;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->values);
    }

    public function isEmpty(): bool
    {
        return $this->values === [];
    }

    /**
     * @return non-negative-int
     */
    public function count(): int
    {
        return \count($this->values);
    }

    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \BadMethodCallException();
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new \BadMethodCallException();
    }
}
