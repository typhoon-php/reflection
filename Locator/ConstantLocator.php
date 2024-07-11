<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\ConstantId;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
interface ConstantLocator
{
    public function locate(ConstantId $id): ?Resource;
}
