<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\Id;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template TId of Id
 * @template TValue
 * @implements \ArrayAccess<TId, TValue>
 * @implements \IteratorAggregate<TId, TValue>
 */
final class DeclarationIdMap implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @var array<non-empty-string, array{TId, TValue}>
     */
    public array $values = [];

    /**
     * @template TNewId of Id
     * @template TNewValue
     * @param TNewId $id
     * @param TNewValue $value
     * @return self<TId|TNewId, TValue|TNewValue>
     */
    public function with(Id $id, mixed $value): self
    {
        /** @var self<TId|TNewId, TValue|TNewValue> */
        $copy = clone $this;
        $copy->values[$id->toString()] = [$id, $value];

        return $copy;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[$offset->toString()]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->values[$offset->toString()][1] ?? throw new \LogicException();
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException();
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException();
    }

    /**
     * @return \Generator<TId, TValue>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->values as [$id, $value]) {
            yield $id => $value;
        }
    }

    public function count(): int
    {
        return \count($this->values);
    }
}
