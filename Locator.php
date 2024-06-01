<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;

/**
 * @api
 */
interface Locator
{
    public function locate(ConstantId|NamedFunctionId|NamedClassId $id): ?Resource;
}
