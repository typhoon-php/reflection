<?php

declare(strict_types=1);

namespace
{
    function adapterCompatibilityTestRootNamespaceFunction() {}
}

namespace AdapterCompatibilityTest\FunctionsInNamespace {

    function functionWitALotOfTypes(
        $noType,
        bool $bool,
        int $int,
        float $float,
        string $string,
        array $array,
        mixed $mixed,
        \Closure $closure,
        object $object,
        int|string $intOrString,
        float|string $floatOrString,
        null|string|object $nullOrStringOrObject,
        // iterable is reflected differently in PHP 8.1 and 8.3 and breaks CI pipeline
        // iterable $iterable,
        // ?iterable $nullableIterable,
        // iterable|int $iterableOrInt,
        int|false $intOrFalse,
        ?string $nullableString,
        \Iterator&\Countable $iteratorAndCountable,
        \Countable&\Iterator $countableAndIterator,
    ) {
    }

    function generatorWithoutType() { yield 1; }

    function generatorWithType(): \Generator { yield 1; }

    function &byReference(string &$a): int {}

    function variadic(string ...$a): void {}

    #[\Attribute(\Attribute::TARGET_ALL|\Attribute::IS_REPEATABLE)]
    final class Attr
    {
        public function __construct(public readonly string $value) {}
        public function __toString(): string { return __FILE__; }
    }

    #[Attr('function')]
    function functionWitAttributes(
        #[Attr('param')]
        string $param
    ): void {}
}
