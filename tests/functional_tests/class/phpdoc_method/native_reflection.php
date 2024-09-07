<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertFalse;

return static function (TyphoonReflector $reflector): void {
    $class = $reflector
        ->withResource(Resource::fromCode(
            <<<'PHP'
                <?php
                /** 
                 * @method m($noType)
                 */
                final class A {}
                PHP,
        ))
        ->reflectClass('A');

    assertEmpty($class->toNativeReflection()->getMethods());
    assertFalse($class->toNativeReflection()->hasMethod('m'));
};
