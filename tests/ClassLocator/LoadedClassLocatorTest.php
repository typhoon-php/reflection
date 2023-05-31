<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\ClassLocator;

use ExtendedTypeSystem\Reflection\ClassLocator\LoadedClassLocatorStub\ClassStub;
use ExtendedTypeSystem\Reflection\ClassLocator\LoadedClassLocatorStub\EnumStub;
use ExtendedTypeSystem\Reflection\ClassLocator\LoadedClassLocatorStub\InterfaceStub;
use ExtendedTypeSystem\Reflection\ClassLocator\LoadedClassLocatorStub\TraitStub;
use ExtendedTypeSystem\Reflection\Source;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;

/**
 * @internal
 */
#[CoversClass(LoadedClassLocator::class)]
final class LoadedClassLocatorTest extends TestCase
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @return array<array{class-string, string}>
     */
    public static function loadedClasses(): array
    {
        return [
            [ClassStub::class, __DIR__ . '/LoadedClassLocatorStub/ClassStub.php'],
            [InterfaceStub::class, __DIR__ . '/LoadedClassLocatorStub/InterfaceStub.php'],
            [EnumStub::class, __DIR__ . '/LoadedClassLocatorStub/EnumStub.php'],
            [TraitStub::class, __DIR__ . '/LoadedClassLocatorStub/TraitStub.php'],
        ];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('loadedClasses')]
    public function testItDoesNotLocateNonLoadedClass(string $class): void
    {
        $locator = new LoadedClassLocator();

        $locatedSource = $locator->locateClass($class);

        assertNull($locatedSource);
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('loadedClasses')]
    #[Depends('testItDoesNotLocateNonLoadedClass')]
    public function testItLocatesLoadedClass(string $class, string $expectedFile): void
    {
        $locator = new LoadedClassLocator();
        $expectedSource = Source::fromFile($expectedFile, 'loaded class reflection');
        class_exists($class);

        $locatedSource = $locator->locateClass($class);

        assertEquals($expectedSource, $locatedSource);
    }

    public function testItDoesNotLocateInternalClass(): void
    {
        $locator = new LoadedClassLocator();

        $locatedSource = $locator->locateClass(\stdClass::class);

        self::assertNull($locatedSource);
    }
}
