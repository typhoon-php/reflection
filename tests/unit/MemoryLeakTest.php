<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PhpParser\Parser as PhpParser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Typhoon\Type;

#[CoversNothing]
final class MemoryLeakTest extends TestCase
{
    private const DIRECTORIES_TO_LOAD = [
        __DIR__ . '/../../src',
        __DIR__ . '/../../vendor/nikic/php-parser/lib',
        __DIR__ . '/../../vendor/phpstan/phpdoc-parser/src',
        __DIR__ . '/../../vendor/psr/simple-cache/src',
        __DIR__ . '/../../vendor/typhoon/type-stringifier/src',
        __DIR__ . '/../../vendor/typhoon/type/src',
    ];
    private const CLASSES = [
        Type\StringType::class,
        Type\NamedObjectType::class,
        Type\ArrayType::class,
        Type\ListType::class,
        Type\NonEmptyStringType::class,
        Type\NonEmptyListType::class,
        Type\TemplateType::class,
    ];

    private static PhpParser $phpParser;

    public static function setUpBeforeClass(): void
    {
        gc_disable();

        foreach (Finder::create()->in(self::DIRECTORIES_TO_LOAD)->name('*.php') as $file) {
            /** @psalm-suppress UnresolvableInclude */
            require_once $file->getPathname();
        }

        self::$phpParser = (new ParserFactory())->createForHostVersion();
    }

    private static function cleanUpParser(): void
    {
        self::$phpParser->parse('');
    }

    public function testTyphoonReflectorClassExistsIsNotLeaking(): void
    {
        foreach (self::CLASSES as $class) {
            $memory = memory_get_usage();

            TyphoonReflector::build(phpParser: self::$phpParser)->classExists($class);
            self::cleanUpParser();

            self::assertLessThanOrEqual($memory, memory_get_usage());
        }
    }

    public function testTyphoonReflectorReflectIsNotLeaking(): void
    {
        // warm up
        TyphoonReflector::build(phpParser: self::$phpParser)->reflectClass(Type\NamedObjectType::class);
        self::cleanUpParser();

        foreach (self::CLASSES as $class) {
            $memory = memory_get_usage();

            TyphoonReflector::build(phpParser: self::$phpParser)->reflectClass($class);
            self::cleanUpParser();

            self::assertLessThanOrEqual($memory, memory_get_usage());
        }
    }

    public function testReflectionSessionClassExistsIsNotLeaking(): void
    {
        $session =    TyphoonReflector::build(phpParser: self::$phpParser)->startSession();
        $session->classExists(Type\NamedObjectType::class);
        $session->flush();
        self::cleanUpParser();

        foreach (self::CLASSES as $class) {
            $memory = memory_get_usage();

            $session->classExists($class);
            $session->flush();
            self::cleanUpParser();

            self::assertLessThanOrEqual($memory, memory_get_usage());
        }
    }

    public function testReflectionSessionReflectIsNotLeaking(): void
    {
        $session =    TyphoonReflector::build(phpParser: self::$phpParser)->startSession();
        $session->reflectClass(Type\NamedObjectType::class);
        $session->flush();
        self::cleanUpParser();

        foreach (self::CLASSES as $class) {
            $memory = memory_get_usage();

            $session->reflectClass($class);
            $session->flush();
            self::cleanUpParser();

            self::assertLessThanOrEqual($memory, memory_get_usage());
        }
    }
}
