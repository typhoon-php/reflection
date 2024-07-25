<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Annotated;

use Typhoon\Type\Type;

/**
 * @api
 */
interface TypeContext
{
    /**
     * @param non-empty-string $unresolvedName
     * @return array{non-empty-string, ?non-empty-string}
     */
    public function resolveConstantName(string $unresolvedName): array;

    /**
     * @param non-empty-string $unresolvedName
     * @return non-empty-string
     */
    public function resolveClassName(string $unresolvedName): string;

    /**
     * @param non-empty-string $unresolvedName
     * @param list<Type> $arguments
     */
    public function resolveNameAsType(string $unresolvedName, array $arguments = []): Type;
}
