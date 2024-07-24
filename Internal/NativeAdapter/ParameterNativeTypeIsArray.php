<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\Type\Type;
use Typhoon\Type\Visitor\DefaultTypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\NativeAdapter
 * @extends DefaultTypeVisitor<bool>
 */
final class ParameterNativeTypeIsArray extends DefaultTypeVisitor
{
    public function array(Type $type, Type $keyType, Type $valueType, array $elements): mixed
    {
        return true;
    }

    public function union(Type $type, array $ofTypes): mixed
    {
        $isNull = new ParameterNativeTypeIsNull();
        $array = false;

        foreach ($ofTypes as $ofType) {
            if ($ofType->accept($isNull)) {
                continue;
            }

            if (!$ofType->accept($this)) {
                return false;
            }

            $array = true;
        }

        return $array;
    }

    protected function default(Type $type): mixed
    {
        return false;
    }
}
