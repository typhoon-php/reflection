<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use Typhoon\Type\types;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

return static function (TyphoonReflector $reflector): void {
    $methods = $reflector
        ->withResource(Resource::fromCode(
            <<<'PHP'
                <?php
                /** 
                 * @method instanceWithoutType()
                 * @method void instanceWithType()
                 * @method static staticWithoutType()
                 * @method static void staticWithType()
                 * @method static static staticStatic()
                 */
                final class A {}
                PHP,
        ))
        ->reflectClass('A')
        ->methods();

    assertFalse($methods['instanceWithoutType']->isStatic());
    assertFalse($methods['instanceWithType']->isStatic());
    assertFalse($methods['staticWithoutType']->isStatic());
    assertTrue($methods['staticWithType']->isStatic());
    assertTrue($methods['staticStatic']->isStatic());
    assertEquals(types::static(resolvedClass: 'A'), $methods['staticStatic']->returnType());
};
