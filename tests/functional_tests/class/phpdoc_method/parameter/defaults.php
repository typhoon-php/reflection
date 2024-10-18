<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use function PHPUnit\Framework\assertSame;

return static function (TyphoonReflector $reflector): void {
    $parameters = $reflector
        ->withResource(new Resource(
            code: <<<'PHP'
                <?php
                /** 
                 * @method m($f=__FILE__, $d=__DIR__, $l=__LINE__, $c=__CLASS__, $m=__METHOD__, $func=__FUNCTION__, $str="\"\n")
                 */
                final class A {}
                PHP,
            file: 'dir/file.php',
        ))
        ->reflectClass('A')
        ->methods()['m']
        ->parameters();

    assertSame($parameters['f']->evaluateDefault(), 'dir/file.php');
    assertSame($parameters['d']->evaluateDefault(), 'dir');
    assertSame($parameters['l']->evaluateDefault(), 3);
    assertSame($parameters['c']->evaluateDefault(), 'A');
    assertSame($parameters['m']->evaluateDefault(), 'A::m');
    assertSame($parameters['func']->evaluateDefault(), 'm');
    assertSame($parameters['str']->evaluateDefault(), "\"\n");
};
