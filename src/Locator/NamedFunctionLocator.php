<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\NamedFunctionId;

/**
 * @api
 */
interface NamedFunctionLocator
{
    public function locate(NamedFunctionId $id): ?Resource;
}
