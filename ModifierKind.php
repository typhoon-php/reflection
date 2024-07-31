<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 */
enum ModifierKind
{
    case Resolved;
    case Native;
    case Annotated;
}
