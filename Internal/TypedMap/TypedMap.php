<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon
 * @readonly
 * @implements \ArrayAccess<Key, mixed>
 * @implements \IteratorAggregate<Key, mixed>
 */
final class TypedMap implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @var \SplObjectStorage<Key, mixed>
     */
    private \SplObjectStorage $values;

    public function __construct()
    {
        $this->values = new \SplObjectStorage();
    }

    public function __clone()
    {
        $this->values = clone $this->values;
    }

    /**
     * @template T
     * @param Key<T> $key
     * @param T $value
     */
    public function with(Key $key, mixed $value): self
    {
        if ($key instanceof OptionalKey && $value === $key->default($this)) {
            if ($this->values->contains($key)) {
                return $this->without($key);
            }

            return $this;
        }

        $copy = clone $this;
        $copy->values->attach($key, $value);

        return $copy;
    }

    public function withMap(self $map): self
    {
        $copy = clone $this;
        $copy->values->addAll($map->values);

        return $copy;
    }

    public function without(Key ...$keys): self
    {
        $copy = clone $this;

        foreach ($keys as $key) {
            $copy->values->detach($key);
        }

        return $copy;
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->values->contains($offset);
    }

    /**
     * @template T
     * @param Key<T> $offset
     * @return T
     */
    public function offsetGet(mixed $offset): mixed
    {
        if ($this->values->contains($offset)) {
            /** @var T */
            return $this->values[$offset];
        }

        if ($offset instanceof OptionalKey) {
            /** @var T */
            return $offset->default($this);
        }

        throw new UndefinedKey($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException(sprintf('%s is immutable', self::class));
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException(sprintf('%s is immutable', self::class));
    }

    /**
     * @return \Generator<Key, mixed>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->values as $key) {
            yield $key => $this->values[$key];
        }
    }

    public function count(): int
    {
        return \count($this->values);
    }

    public function __serialize(): array
    {
        $data = [];

        foreach ($this as $key => $value) {
            $data[$key::class . '::' . $key->name] = $value;
        }

        return $data;
    }

    /**
     * @param array<non-empty-string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $this->values = new \SplObjectStorage();

        foreach ($data as $enum => $value) {
            $key = \constant($enum);
            \assert($key instanceof Key);
            $this->values->attach($key, $value);
        }
    }
}
