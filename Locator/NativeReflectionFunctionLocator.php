<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Locator;
use Typhoon\Reflection\Resource;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 */
final class NativeReflectionFunctionLocator implements Locator
{
    public function locate(ConstantId|FunctionId|ClassId $id): ?Resource
    {
        if (!$id instanceof FunctionId) {
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
            $baseData = $baseData->with(Data::Extension(), $extension);
        }

        return new Resource($file, $baseData);
    }
}
