<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\ClassLocator;

use ExtendedTypeSystem\Reflection\Source;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ComposerAutoloadClassLocator::class)]
final class ComposerAutoloadClassLocatorTest extends TestCase
{
    public function testItCorrectlyLoadsDefaultAutoloaderAsLibrary(): void
    {
        new ComposerAutoloadClassLocator();

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsIfFileDoesNotReturnAutoloader(): void
    {
        $file = __DIR__ . '/invalid_autoloader.php';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($file . ' is not a valid autoload file. Composer\Autoload\ClassLoader is expected to be returned, got null.');

        new ComposerAutoloadClassLocator(__DIR__ . '/invalid_autoloader.php');
    }

    public function testItLocatesClass(): void
    {
        $expectedSource = Source::fromFile(__FILE__, 'composer autoloader');
        $locator = new ComposerAutoloadClassLocator(__DIR__ . '/../../vendor/autoload.php');

        $locatedSource = $locator->locateClass(self::class);

        self::assertEquals($expectedSource, $locatedSource);
    }

    public function testItReturnsNullForUnknownClass(): void
    {
        $locator = new ComposerAutoloadClassLocator(__DIR__ . '/../../vendor/autoload.php');

        $locatedSource = $locator->locateClass(\stdClass::class);

        self::assertNull($locatedSource);
    }
}
