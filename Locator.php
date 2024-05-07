<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\FunctionId;

/**
 * @api
 */
interface Locator
{
    public function locate(ConstantId|FunctionId|ClassId $id): ?Resource;
}
