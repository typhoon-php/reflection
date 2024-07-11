<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
final class NativeReflectionClassLocator implements NamedClassLocator
{
    public function __construct(
        private readonly bool $autoload = false,
    ) {}

    public function locate(NamedClassId $id): ?Resource
    {
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

        $baseData = new TypedMap();
        $extension = $reflection->getExtensionName();

        if ($extension !== false) {
            $baseData = $baseData->set(Data::PhpExtension, $extension);
        }

        return Resource::fromFile($file, $baseData);
    }
}
