<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

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
final class NativeReflectionFunctionLocator implements Locator
{
    public function locate(ConstantId|NamedFunctionId|NamedClassId $id): ?Resource
    {
        if (!$id instanceof NamedFunctionId) {
            return null;
        }

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
            $baseData = $baseData->set(Data::PhpExtension, $extension);
        }

        return new Resource($file, $baseData);
    }
}
