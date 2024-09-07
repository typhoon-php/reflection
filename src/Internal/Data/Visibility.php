<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum Visibility
{
    case Public;
    case Protected;
    case Private;
}
