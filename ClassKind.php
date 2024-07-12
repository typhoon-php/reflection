<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 */
enum ClassKind: string
{
    case Class_ = 'class';
    case Interface = 'interface';
    case Enum = 'enum';
    case Trait = 'trait';
}
