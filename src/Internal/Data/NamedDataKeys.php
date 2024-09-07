<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\TypedMap\OptionalKey;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements OptionalKey<array<non-empty-string, TypedMap>>
 */
enum NamedDataKeys implements OptionalKey
{
    case Aliases;
    case Constants;
    case Methods;
    case Parameters;
    case Properties;
    case Templates;

    public function default(TypedMap $map): mixed
    {
        return [];
    }
}
