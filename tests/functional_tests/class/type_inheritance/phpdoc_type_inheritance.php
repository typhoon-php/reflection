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
                abstract class A
                {
                    /** @return non-empty-string */
                    abstract public function a(): string;
                }
                class B extends A
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
