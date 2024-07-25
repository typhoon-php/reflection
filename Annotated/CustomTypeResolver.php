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
     * @param non-empty-string $name
     * @param list<Type> $typeArguments
     */
    public function resolveCustomType(string $name, array $typeArguments, TypeContext $context): ?Type;
}
