<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use Typhoon\Type\types;
use function PHPUnit\Framework\assertEquals;

return static function (TyphoonReflector $reflector): void {
    $reflection = $reflector->withResource(Resource::fromCode(
        <<<'PHP'
            <?php

            abstract class A
            {
                /**
                 * @param non-empty-string $name
                 */
                public function a(string $name) {}
            }

            class B extends A
            {
                public function a(string $differentName, int $name = 0) {}
            }
            PHP,
    ))->reflectClass('B');

    assertEquals(types::nonEmptyString, $reflection->methods()['a']->parameters()['differentName']->type());
    assertEquals(types::int, $reflection->methods()['a']->parameters()['name']->type());
};
