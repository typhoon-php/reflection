<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
final class NativeReflectionClassLocator implements NamedClassLocator
{
    public function locate(NamedClassId $id): ?Resource
    {
        try {
            $reflection = new \ReflectionClass($id->name);
        } catch (\ReflectionException) {
            return null;
        }

        $file = $reflection->getFileName();

        if ($file === false) {
            return null;
        }

        $baseData = new TypedMap();
        $extension = $reflection->getExtensionName();

        if ($extension !== false) {
            $baseData = $baseData->with(Data::PhpExtension, $extension);
        }

        return Resource::fromFile($file, $baseData);
    }
}
