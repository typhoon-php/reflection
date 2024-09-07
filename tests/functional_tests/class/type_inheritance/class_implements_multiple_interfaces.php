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
                class B implements I1, I2
                {
                    public function a(): string { return '1'; }
                }
                PHP,
        ))
        ->reflectClass('B')
        ->methods()['a'];

    assertEquals(types::string, $method->returnType(TypeKind::Native));
    assertEquals(types::nonEmptyString, $method->returnType(TypeKind::Annotated));
    assertEquals(types::nonEmptyString, $method->returnType());
};
