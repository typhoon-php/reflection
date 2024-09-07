<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class AdapterCompletenessTest extends TestCase
{
    /**
     * @param class-string $class
     */
    #[TestWith([FunctionAdapter::class])]
    #[TestWith([AttributeAdapter::class])]
    #[TestWith([ClassConstantAdapter::class])]
    #[TestWith([ClassAdapter::class])]
    #[TestWith([EnumAdapter::class])]
    #[TestWith([MethodAdapter::class])]
    #[TestWith([ParameterAdapter::class])]
    #[TestWith([PropertyAdapter::class])]
    #[TestWith([NamedTypeAdapter::class])]
    #[TestWith([UnionTypeAdapter::class])]
    #[TestWith([IntersectionTypeAdapter::class])]
    #[TestWith([EnumBackedCaseAdapter::class])]
    public function testAllMethodsImplemented(string $class): void
    {
        foreach ((new \ReflectionClass($class))->getMethods() as $method) {
            self::assertSame(
                $class,
                $method->class,
                \sprintf('Method %s::%s() is not overridden in %s', $method->class, $method->name, $class),
            );
        }
    }
}
