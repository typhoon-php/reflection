<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use Typhoon\Type\types;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;

return static function (TyphoonReflector $reflector): void {
    $parameters = $reflector
        ->withResource(Resource::fromCode(
            <<<'PHP'
                <?php
                namespace X;
                
                final class A
                {
                    public function method(
                        $noTypeNoDefault,
                        $noTypeWithDefault = null,
                        ?string $nullableTypeNoDefault,
                        ?string $nullableTypeWithDefault = null,
                    ) {}
                }
                PHP,
        ))
        ->reflectClass('X\A')
        ->methods()['method']
        ->parameters();

    assertNull($parameters['noTypeNoDefault']->type(TypeKind::Native));
    assertNull($parameters['noTypeWithDefault']->type(TypeKind::Native));
    assertEquals(types::nullable(types::string), $parameters['nullableTypeNoDefault']->type(TypeKind::Native));
    assertEquals(types::nullable(types::string), $parameters['nullableTypeWithDefault']->type(TypeKind::Native));
};
