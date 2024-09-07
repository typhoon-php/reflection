<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use function PHPUnit\Framework\assertSame;

return static function (TyphoonReflector $reflector): void {
    $aliases = $reflector
        ->withResource(Resource::fromCode(<<<'PHP'
            <?php
            /** 
             * @psalm-type First = string
             * @phpstan-type Second = int
             */
            class A {}
            PHP))
        ->reflectClass('A')
        ->aliases();

    assertSame(3, $aliases['First']->location()?->startLine);
    assertSame(3, $aliases['First']->location()?->endLine);
    assertSame(4, $aliases['Second']->location()?->startLine);
    assertSame(4, $aliases['Second']->location()?->endLine);
};
