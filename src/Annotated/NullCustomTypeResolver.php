<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Annotated;

use Typhoon\Type\Type;

/**
 * @api
 */
final class NullCustomTypeResolver implements CustomTypeResolver
{
    public function resolveCustomType(string $unresolvedName, array $typeArguments, TypeContext $context): ?Type
    {
        return null;
    }
}
