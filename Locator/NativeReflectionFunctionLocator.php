<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\TypedMap\TypedMap;

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

        $data = new TypedMap();
        $extension = $reflection->getExtensionName();

        if ($extension !== false) {
            $data = $data->with(Data::PhpExtension, $extension);
        }

        return Resource::fromFile($file, $data);
    }
}
