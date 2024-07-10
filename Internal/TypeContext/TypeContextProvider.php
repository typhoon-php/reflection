<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\TypeContext;

interface TypeContextProvider
{
    public function get(): TypeContext;
}
