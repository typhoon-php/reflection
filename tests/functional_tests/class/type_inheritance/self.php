<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use Typhoon\Type\types;
use function PHPUnit\Framework\assertEquals;

return static function (TyphoonReflector $reflector): void {
    $reflector = $reflector->withResource(Resource::fromCode(
        <<<'PHP'
            <?php
            abstract class A
            {
                public function a(): self;
            }
            abstract class B extends A {}
            abstract class C extends B {}
            PHP,
    ));

    assertEquals(types::self(resolvedClass: 'A'), $reflector->reflectClass('A')->methods()['a']->returnType());
    assertEquals(types::self(resolvedClass: 'A'), $reflector->reflectClass('B')->methods()['a']->returnType());
    assertEquals(types::self(resolvedClass: 'A'), $reflector->reflectClass('C')->methods()['a']->returnType());
};
