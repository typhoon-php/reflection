<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Annotated;

use Typhoon\Type\Type;

/**
 * @api
 */
interface CustomTypeResolver
{
    /**
     * @param non-empty-string $unresolvedName
     * @param list<Type> $typeArguments
     */
    public function resolveCustomType(string $unresolvedName, array $typeArguments, TypeContext $context): ?Type;
}
