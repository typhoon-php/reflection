<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

return static function (TyphoonReflector $reflector): void {
    $parameters = $reflector
        ->withResource(Resource::fromCode(
            <<<'PHP'
                <?php
                /** 
                 * @method m($param)
                 */
                final class A {}
                PHP,
        ))
        ->reflectClass('A')
        ->methods()['m']
        ->parameters();

    assertTrue($parameters['param']->isAnnotated());
    assertFalse($parameters['param']->isNative());
};
