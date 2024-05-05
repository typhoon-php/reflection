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
final class NativeReflectionClassLocator implements Locator
{
    public function __construct(
        private readonly bool $autoload = false,
    ) {}

    public function locate(ConstantId|FunctionId|ClassId|AnonymousClassId $id): ?Resource
    {
        if (!$id instanceof ClassId) {
            return null;
        }

        if (!$this->autoload
            && !class_exists($id->name, autoload: false)
            && !interface_exists($id->name, autoload: false)
            && !trait_exists($id->name, autoload: false)
        ) {
            return null;
        }

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
        $data = new TypedMap();

        if ($extension !== false) {
            $data = $data->with(Data::Extension(), $extension);
        }

        return Resource::fromFile($file, $data);
    }
}
