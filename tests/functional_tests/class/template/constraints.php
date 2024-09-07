<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use Typhoon\Type\types;
use function PHPUnit\Framework\assertEquals;

return static function (TyphoonReflector $reflector): void {
    $templates = $reflector
        ->withResource(Resource::fromCode(<<<'PHP'
            <?php
            /** 
             * @template TMixed
             * @template TString of string 
             * @template TComplex of iterable<TMixed, TString> 
             */
            class A {}
            PHP))
        ->reflectClass('A')
        ->templates();

    assertEquals(types::mixed, $templates['TMixed']->constraint());
    assertEquals(types::string, $templates['TString']->constraint());
    assertEquals(
        types::iterable(
            types::classTemplate('A', 'TMixed'),
            types::classTemplate('A', 'TString'),
        ),
        $templates['TComplex']->constraint(),
    );
};
