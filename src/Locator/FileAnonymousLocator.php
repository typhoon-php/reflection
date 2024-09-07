<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;

/**
 * @api
 */
final class FileAnonymousLocator implements AnonymousLocator
{
    public function locate(AnonymousFunctionId|AnonymousClassId $id): ?Resource
    {
        return Resource::fromFile($id->file);
    }
}
