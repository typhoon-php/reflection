<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Context;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface ContextProvider
{
    public function current(): Context;
}
