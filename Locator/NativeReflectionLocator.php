<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\Reflection\Locator;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
final class NativeReflectionLocator implements Locator
{
    public function __construct(
        private readonly bool $autoload = true,
    ) {}

    public function locate(ClassId|FunctionId $id): ?Resource
    {
        try {
            $reflection = $this->reflect($id);
        } catch (\ReflectionException) {
            return null;
        }

        if ($reflection === null) {
            return null;
        }

        $file = $reflection->getFileName();

        if ($file === false) {
            return null;
        }

        $extension = $reflection->getExtensionName();

        return Resource::fromFile($file, $extension === false ? null : $extension);
    }

    /**
     * @throws \ReflectionException
     */
    private function reflect(ClassId|FunctionId $id): null|\ReflectionClass|\ReflectionFunction
    {
        if ($id instanceof FunctionId) {
            /** @psalm-suppress ArgumentTypeCoercion */
            return new \ReflectionFunction($id->name);
        }

        if (!$this->autoload
            && !class_exists($id->name, false)
            && !interface_exists($id->name, false)
            && !trait_exists($id->name, false)
        ) {
            return null;
        }

        return new \ReflectionClass($id->name);
    }
}
