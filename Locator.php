<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;

/**
 * @api
 */
interface Locator
{
    public function locate(ClassId|FunctionId $id): ?Resource;
}
