<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 * @template-covariant T
 * @implements \ArrayAccess<int|string, T>
 * @implements \IteratorAggregate<non-empty-string, T>
 * @psalm-suppress InvalidTemplateParam
 */
final class NameMap implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param array<non-empty-string, T> $values
     */
    public function __construct(
        private readonly array $values,
    ) {}

    public function offsetExists(mixed $offset): bool
    {
        if (\is_int($offset)) {
            return $offset >= 0 && $offset < $this->count();
        }

        return isset($this->values[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (\is_int($offset)) {
            $name = $this->names()[$offset] ?? throw new \RuntimeException(sprintf('Offset %s is not set', $offset));

            return $this->values[$name];
        }

        return $this->values[$offset] ?? throw new \RuntimeException(sprintf('Offset %s is not set', $offset));
    }

    /**
     * @template TNew
     * @param callable(T, non-empty-string): TNew $mapper
     * @return self<TNew>
     */
    public function map(callable $mapper): self
    {
        $values = [];

        foreach ($this->values as $name => $value) {
            $values[$name] = $mapper($value, $name);
        }

        return new self($values);
    }

    /**
     * @param callable(T, non-empty-string): bool $filter
     * @return self<T>
     */
    public function filter(callable $filter): self
    {
        $values = [];

        foreach ($this->values as $name => $value) {
            if ($filter($value, $name)) {
                $values[$name] = $value;
            }
        }

        return new self($values);
    }

    /**
     * @return list<non-empty-string>
     */
    public function names(): array
    {
        return array_keys($this->values);
    }

    /**
     * @return list<T>
     */
    public function toList(): array
    {
        return array_values($this->values);
    }

    /**
     * @return array<non-empty-string, T>
     */
    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * @param callable(T, non-empty-string): bool $predicate
     */
    public function any(callable $predicate): bool
    {
        foreach ($this->values as $name => $value) {
            if ($predicate($value, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param callable(T, non-empty-string): bool $predicate
     */
    public function all(callable $predicate): bool
    {
        foreach ($this->values as $name => $value) {
            if (!$predicate($value, $name)) {
                return false;
            }
        }

        return true;
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
        throw new \BadMethodCallException(sprintf('%s is immutable', self::class));
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new \BadMethodCallException(sprintf('%s is immutable', self::class));
    }
}
