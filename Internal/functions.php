<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template TKey of array-key
 * @template TValue
 * @template TNewValue
 * @param iterable<TKey, TValue> $iterable
 * @param callable(TValue, TKey): TNewValue $mapper
 * @return (
 *     $iterable is non-empty-list ? non-empty-list<TNewValue> :
 *     $iterable is list ? list<TNewValue> :
 *     $iterable is non-empty-array ? non-empty-array<TKey, TNewValue> :
 *     array<TKey, TNewValue>
 * )
 */
function map(iterable $iterable, callable $mapper): array
{
    $mapped = [];

    foreach ($iterable as $key => $value) {
        $mapped[$key] = $mapper($value, $key);
    }

    return $mapped;
}
