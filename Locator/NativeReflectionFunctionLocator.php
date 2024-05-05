<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\AnonymousClassId;
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
    public function locate(ConstantId|FunctionId|ClassId|AnonymousClassId $id): ?Resource
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
        $data = new TypedMap();

        if ($extension !== false) {
            $data = $data->with(Data::Extension(), $extension);
        }

        return Resource::fromFile($file, $data);
    }
}
