<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Locator;
use Typhoon\Reflection\Resource;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 */
final class AnonymousClassLocator implements Locator
{
    public function locate(ConstantId|NamedFunctionId|NamedClassId|AnonymousClassId $id): ?Resource
    {
        if (!$id instanceof AnonymousClassId) {
            return null;
        }

        return new Resource(
            file: $id->file,
            baseData: (new TypedMap())->set(Data::File, $id->file),
        );
    }
}
