<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\NamedClassId;

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

        $extension = $reflection->getExtensionName();

        return Resource::fromFile(
            file: $file,
            extension: $extension === false ? null : $extension,
        );
    }
}
