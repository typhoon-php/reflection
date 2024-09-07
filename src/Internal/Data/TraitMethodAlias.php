<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class TraitMethodAlias
{
    /**
     * @param non-empty-string $trait
     * @param non-empty-string $method
     * @param ?non-empty-string $newName
     */
    public function __construct(
        public readonly string $trait,
        public readonly string $method,
        public readonly ?string $newName = null,
        public readonly ?Visibility $newVisibility = null,
    ) {}
}
