<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 */
enum Kind
{
    case Native;
    case Annotated;
    case Resolved;
}
