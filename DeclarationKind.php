<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 */
enum DeclarationKind
{
    case Native;
    case Annotated;
}
