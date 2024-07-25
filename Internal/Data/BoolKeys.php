<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Reflection\Internal\TypedMap\OptionalKey;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements OptionalKey<bool>
 */
enum BoolKeys implements OptionalKey
{
    case AttributeRepeated;
    case InternallyDefined;
    case IsAbstract;
    case AnnotatedFinal;
    case AnnotatedReadonly;
    case ReturnsReference;
    case EnumCase;
    case Generator;
    case NativeFinal;
    case NativeReadonly;
    case Promoted;
    case IsStatic;
    case Variadic;
    case Annotated;
    case Optional;
    case Cloneable;

    public function default(TypedMap $map): mixed
    {
        return false;
    }
}
