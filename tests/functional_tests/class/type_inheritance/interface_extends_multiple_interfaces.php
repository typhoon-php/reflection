<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use Typhoon\Type\types;
use function PHPUnit\Framework\assertEquals;

return static function (TyphoonReflector $reflector): void {
    $method = $reflector
        ->withResource(Resource::fromCode(
            <<<'PHP'
                <?php
                interface I1
                {
                    public function a(): string;
                }
                interface I2
                {
                    /** @return non-empty-string */
                    public function a(): string;
                }
                interface I12 extends I1, I2
                {
                }
                PHP,
        ))
        ->reflectClass('I12')
        ->methods()['a'];

    assertEquals(types::string, $method->returnType(TypeKind::Native));
    assertEquals(types::nonEmptyString, $method->returnType(TypeKind::Annotated));
    assertEquals(types::nonEmptyString, $method->returnType());
};
