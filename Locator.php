<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\DeclarationId\NamedClassId;

/**
 * @api
 */
interface Locator
{
    public function locate(ConstantId|FunctionId|NamedClassId $id): ?Resource;
}
