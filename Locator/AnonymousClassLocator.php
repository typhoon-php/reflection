<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\Reflection\Locator;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
final class AnonymousClassLocator implements Locator
{
    public function locate(ConstantId|FunctionId|ClassId|AnonymousClassId $id): ?Resource
    {
        if (!$id instanceof AnonymousClassId) {
            return null;
        }

        return Resource::fromFile($id->file);
    }
}
