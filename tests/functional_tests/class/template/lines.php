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
             * @template TSingleLine
             * @template TMultiLine Description Line 1
             *                      Description Line 2
             */
            class A {}
            PHP))
        ->reflectClass('A')
        ->templates();

    assertSame(3, $templates['TSingleLine']->location()?->startLine);
    assertSame(4, $templates['TSingleLine']->location()?->startColumn);
    assertSame(3, $templates['TSingleLine']->location()?->endLine);
    assertSame(25, $templates['TSingleLine']->location()?->endColumn);

    assertSame(4, $templates['TMultiLine']->location()?->startLine);
    assertSame(4, $templates['TMultiLine']->location()?->startColumn);
    assertSame(5, $templates['TMultiLine']->location()?->endLine);
    assertSame(43, $templates['TMultiLine']->location()?->endColumn);
};
