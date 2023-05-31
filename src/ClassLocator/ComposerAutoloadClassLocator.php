<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\ClassLocator;

use Composer\Autoload\ClassLoader;
use ExtendedTypeSystem\Reflection\ClassLocator;
use ExtendedTypeSystem\Reflection\Source;

/**
 * @api
 */
final class ComposerAutoloadClassLocator implements ClassLocator
{
    private readonly ClassLoader $classLoader;

    public function __construct(?string $autoloadFile = null)
    {
        $autoloadFile ??= self::findAutoloadFile();
        /** @psalm-suppress UnresolvableInclude */
        $classLoader = require $autoloadFile;

        if (!$classLoader instanceof ClassLoader) {
            throw new \InvalidArgumentException(sprintf(
                '%s is not a valid autoload file. %s is expected to be returned, got %s.',
                realpath($autoloadFile),
                ClassLoader::class,
                get_debug_type($classLoader),
            ));
        }

        $this->classLoader = $classLoader;
    }

    private static function findAutoloadFile(): string
    {
        if (!class_exists(ClassLoader::class, autoload: false)) {
            throw new \RuntimeException('Composer autoloader is not available, please provide autoload file explicitly.');
        }

        $classLoaderFile = (new \ReflectionClass(ClassLoader::class))->getFileName();

        return \dirname($classLoaderFile, 2) . '/autoload.php';
    }

    public function locateClass(string $class): ?Source
    {
        $file = $this->classLoader->findFile($class);

        if ($file === false) {
            return null;
        }

        return Source::fromFile($file, 'composer autoloader');
    }
}
