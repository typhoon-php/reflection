<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Hook;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum HookPriorities
{
    public const COMPLETE_REFLECTION = 0;
    public const CLEAN_UP = -1000;
}
