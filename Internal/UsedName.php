<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class UsedName
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly string $name,
        // public readonly array $arguments = [],
    ) {}
}
