<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

return static function (TyphoonReflector $reflector): void {
    $parameters = $reflector
        ->withResource(new Resource(
            <<<'PHP'
                <?php
                final class A
                {
                    public function method(
                        $notDeprecated,
                        /** @deprecated */
                        $deprecated,
                        /** @deprecated Message */
                        $deprecatedWithMessage,
                    ) {}
                }
                PHP,
        ))
        ->reflectClass('A')
        ->methods()['method']
        ->parameters();

    assertFalse($parameters['notDeprecated']->isDeprecated());
    assertNull($parameters['notDeprecated']->deprecation());
    assertTrue($parameters['deprecated']->isDeprecated());
    assertEquals(new Deprecation(), $parameters['deprecated']->deprecation());
    assertTrue($parameters['deprecatedWithMessage']->isDeprecated());
    assertEquals(new Deprecation('Message'), $parameters['deprecatedWithMessage']->deprecation());
};
