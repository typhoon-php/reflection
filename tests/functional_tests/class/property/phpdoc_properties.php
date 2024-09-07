<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use Typhoon\Type\types;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

return static function (TyphoonReflector $reflector): void {
    $properties = $reflector
        ->withResource(Resource::fromCode(
            <<<'PHP'
                <?php
                /** 
                 * @property non-empty-string $prop1
                 * @property-read positive-int $prop2
                 */
                final class A {}
                PHP,
        ))
        ->reflectClass('A')
        ->properties();

    $prop1 = $properties['prop1'];
    assertTrue($prop1->isAnnotated());
    assertFalse($prop1->isNative());
    assertFalse($prop1->isReadonly(ModifierKind::Native));
    assertFalse($prop1->isReadonly(ModifierKind::Annotated));
    assertFalse($prop1->isReadonly());
    assertNull($prop1->type(TypeKind::Native));
    assertSame(types::nonEmptyString, $prop1->type(TypeKind::Annotated));
    assertSame(types::nonEmptyString, $prop1->type());

    $prop2 = $properties['prop2'];
    assertTrue($prop2->isAnnotated());
    assertFalse($prop2->isNative());
    assertFalse($prop2->isReadonly(ModifierKind::Native));
    assertTrue($prop2->isReadonly(ModifierKind::Annotated));
    assertTrue($prop2->isReadonly());
    assertNull($prop2->type(TypeKind::Native));
    assertSame(types::positiveInt, $prop2->type(TypeKind::Annotated));
    assertSame(types::positiveInt, $prop2->type());
};
