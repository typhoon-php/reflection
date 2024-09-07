<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use function PHPUnit\Framework\assertSame;

return static function (TyphoonReflector $reflector): void {
    $templates = $reflector
        ->withResource(Resource::fromCode(<<<'PHP'
            <?php
            /** 
             * @template T0
             * @psalm-type X = int
             * @template T1
             * Text
             * @template T2
             */
            class A {}
            PHP))
        ->reflectClass('A')
        ->templates();

    assertSame(0, $templates['T0']->index());
    assertSame(1, $templates['T1']->index());
    assertSame(2, $templates['T2']->index());
};
