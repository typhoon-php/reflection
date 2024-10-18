<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\NamedFunctionId;

/**
 * @api
 */
final class NativeReflectionFunctionLocator implements NamedFunctionLocator
{
    public function locate(NamedFunctionId $id): ?Resource
    {
        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            $reflection = new \ReflectionFunction($id->name);
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
