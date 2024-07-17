<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Reflection\Resource;

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
        $baseData = new TypedMap();

        if ($extension !== false) {
            $baseData = $baseData->with(Data::PhpExtension, $extension);
        }

        return Resource::fromFile($file, $baseData);
    }
}
