<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon
 */
final class InheritedName
{
    /**
     * @param non-empty-string $name
     * @param list<Type> $arguments
     */
    public function __construct(
        public readonly string $name,
        public readonly array $arguments = [],
    ) {}
}
