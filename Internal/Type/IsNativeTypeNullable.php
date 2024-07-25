<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Type;

use Typhoon\Type\Type;
use Typhoon\Type\Visitor\DefaultTypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @extends DefaultTypeVisitor<bool>
 */
final class IsNativeTypeNullable extends DefaultTypeVisitor
{
    public function null(Type $type): mixed
    {
        return true;
    }

    public function union(Type $type, array $ofTypes): mixed
    {
        foreach ($ofTypes as $ofType) {
            if ($ofType->accept($this)) {
                return true;
            }
        }

        return false;
    }

    public function mixed(Type $type): mixed
    {
        return true;
    }

    protected function default(Type $type): mixed
    {
        return false;
    }
}
