<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Composer\Autoload\ClassLoader;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\Reflection\Locator;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
final class ComposerLocator implements Locator
{
    public static function isSupported(): bool
    {
        return class_exists(ClassLoader::class);
    }

    public function locate(ClassId|FunctionId $id): ?Resource
    {
        if ($id instanceof FunctionId) {
            return null;
        }

        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            $file = $loader->findFile($id->name);

            if ($file !== false) {
                \assert($file !== '');

                return Resource::fromFile($file);
            }
        }

        return null;
    }
}
