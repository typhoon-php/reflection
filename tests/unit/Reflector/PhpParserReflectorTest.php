<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectorTests\JustClass;
use ReflectorTests\NativeFinalClass;
use ReflectorTests\ReadonlyPhp82Class;
use ReflectorTests\PhpDocTaggedFinalAndReadonlyClass;
use Typhoon\Reflection\TyphoonReflector;

#[CoversClass(PhpParserReflector::class)]
#[CoversFunction('reflectClass')]
final class PhpParserReflectorTest extends TestCase
{
    private const CLASSES = __DIR__ . '/classes.php';
    private const CLASSES_82 = __DIR__ . '/classes_82.php';
    
    /**
     * @return \Generator<class-string>
     */
    public static function justClasses(): \Generator
    {
        require_once self::CLASSES;
        
        yield [JustClass::class];
    }
    
    /**
     * @return \Generator<class-string>
     */
    public static function readonlyClasses(): \Generator
    {
        require_once self::CLASSES;
        
        yield [PhpDocTaggedFinalAndReadonlyClass::class];
        
        if (\PHP_VERSION_ID >= 80200) {
            require_once self::CLASSES_82;
            yield [ReadonlyPhp82Class::class];
        }
    }
    
    /**
     * @return \Generator<class-string>
     */
    public static function finalClasses(): \Generator
    {
        require_once self::CLASSES;
        
        yield [PhpDocTaggedFinalAndReadonlyClass::class];
        yield [NativeFinalClass::class];
    }
    
    /**
     * @param class-string $className
     */
    #[DataProvider('justClasses')]
    public function testClassReflectionIsFinalReturnsFalseIfClassIsNotFinal(string $className): void
    {
        $classReflection = TyphoonReflector::build()->reflectClass($className);
        
        self::assertFalse($classReflection->isFinal());
    }
    
    /**
     * @param class-string $className
     */
    #[DataProvider('justClasses')]
    public function testClassReflectionIsReadonlyReturnsFalseIfClassIsNotReadonly(string $className): void
    {
        $classReflection = TyphoonReflector::build()->reflectClass($className);
        
        self::assertFalse($classReflection->isReadOnly());
    }
    
    /**
     * @param class-string $className
     */
    #[DataProvider('finalClasses')]
    public function testClassReflectionIsFinalReturnsTrueIfClassIsFinal(string $className): void
    {
        $classReflection = TyphoonReflector::build()->reflectClass($className);
    
        self::assertTrue($classReflection->isFinal());
    }
    
    /**
     * @param class-string $className
     */
    #[DataProvider('readonlyClasses')]
    public function testClassReflectionIsReadonlyReturnsTrueIfClassIsReadonly(string $className): void
    {
        $classReflection = TyphoonReflector::build()->reflectClass($className);
    
        self::assertTrue($classReflection->isReadOnly());
    }
}
