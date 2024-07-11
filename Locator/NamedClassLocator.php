<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
interface NamedClassLocator
{
    public function locate(NamedClassId $id): ?Resource;
}
