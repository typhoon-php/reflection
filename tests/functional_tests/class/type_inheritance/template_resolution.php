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

            /** @template T */
            trait TR
            {
                /** @var T */
                public mixed $property;
            }
            
            /** @template T */
            abstract class A
            {
                /** @use TR<T> */
                use TR;
            }

            /** @extends A<string> */
            final class B extends A {}
            PHP,
    ))->reflectClass('B');

    assertEquals(types::string, $reflection->properties()['property']->type());
};
