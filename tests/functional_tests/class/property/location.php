<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use function PHPUnit\Framework\assertEquals;

return static function (TyphoonReflector $reflector): void {
    $properties = $reflector
        ->withResource(Resource::fromCode(
            <<<'PHP'
                <?php
                final class A
                {
                    public $prop;

                    var $varProp;

                    /** PHPDoc */
                    public $propWithPhpDoc;

                    #[Attr]
                    public $propWithAttr;

                    /** PHPDoc */
                    #[Attr]
                    public $propWithPhpDocAndAttr;
                }
                PHP,
        ))
        ->reflectClass('A')
        ->properties();

    assertEquals(
        new Location(startPosition: 26, endPosition: 39, startLine: 4, endLine: 4, startColumn: 5, endColumn: 18),
        $properties['prop']->location(),
    );
    assertEquals(
        new Location(startPosition: 45, endPosition: 58, startLine: 6, endLine: 6, startColumn: 5, endColumn: 18),
        $properties['varProp']->location(),
    );
    assertEquals(
        new Location(startPosition: 82, endPosition: 105, startLine: 9, endLine: 9, startColumn: 5, endColumn: 28),
        $properties['propWithPhpDoc']->location(),
    );
    assertEquals(
        new Location(startPosition: 123, endPosition: 144, startLine: 12, endLine: 12, startColumn: 5, endColumn: 26),
        $properties['propWithAttr']->location(),
    );
    assertEquals(
        new Location(startPosition: 180, endPosition: 210, startLine: 16, endLine: 16, startColumn: 5, endColumn: 35),
        $properties['propWithPhpDocAndAttr']->location(),
    );
};
