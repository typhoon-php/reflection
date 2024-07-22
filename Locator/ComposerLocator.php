<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Composer\Autoload\ClassLoader;
use Typhoon\DeclarationId\NamedClassId;

/**
 * @api
 */
final class ComposerLocator implements NamedClassLocator
{
    public static function isSupported(): bool
    {
        return class_exists(ClassLoader::class);
    }

    public function locate(NamedClassId $id): ?Resource
    {
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
