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
            abstract class A {}
            abstract class B extends A
            {
                public function a(): parent;
            }
            abstract class C extends B {}
            PHP,
    ));

    assertEquals(types::parent(resolvedClass: 'A'), $reflector->reflectClass('B')->methods()['a']->returnType());
    assertEquals(types::parent(resolvedClass: 'A'), $reflector->reflectClass('C')->methods()['a']->returnType());
};
