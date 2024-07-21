<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Context;

interface ContextProvider
{
    public function get(): Context;
}
