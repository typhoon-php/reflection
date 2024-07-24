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
final class ParameterNativeTypeIsCallable extends DefaultTypeVisitor
{
    public function callable(Type $type, array $parameters, Type $returnType): mixed
    {
        return true;
    }

    public function union(Type $type, array $ofTypes): mixed
    {
        $isNull = new ParameterNativeTypeIsNull();
        $callable = false;

        foreach ($ofTypes as $ofType) {
            if ($ofType->accept($isNull)) {
                continue;
            }

            if (!$ofType->accept($this)) {
                return false;
            }

            $callable = true;
        }

        return $callable;
    }

    protected function default(Type $type): mixed
    {
        return false;
    }
}
