<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;

/**
 * @api
 */
interface AnonymousLocator
{
    public function locate(AnonymousFunctionId|AnonymousClassId $id): ?Resource;
}
