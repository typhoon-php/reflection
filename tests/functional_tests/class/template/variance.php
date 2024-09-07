<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use Typhoon\Type\Variance;
use function PHPUnit\Framework\assertSame;

return static function (TyphoonReflector $reflector): void {
    $templates = $reflector
        ->withResource(Resource::fromCode(<<<'PHP'
            <?php
            /** 
             * @template TInvariant
             * @template-covariant TCovariant
             * @template-contravariant TContravariant
             */
            class A {}
            PHP))
        ->reflectClass('A')
        ->templates();

    assertSame(Variance::Invariant, $templates['TInvariant']->variance());
    assertSame(Variance::Covariant, $templates['TCovariant']->variance());
    assertSame(Variance::Contravariant, $templates['TContravariant']->variance());
};
