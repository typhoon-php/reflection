<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\TypedMap\TypedMap;

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

        $data = new TypedMap();
        $extension = $reflection->getExtensionName();

        if ($extension !== false) {
            $data = $data->with(Data::PhpExtension, $extension);
        }

        return Resource::fromFile($file, $data);
    }
}
