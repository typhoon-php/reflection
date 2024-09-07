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

            final class A
            {
                public self $self;
                
                /** @var static */
                public $static;
            }
            PHP,
    ))->reflectClass('A');

    assertEquals(types::self(resolvedClass: 'A'), $reflection->properties()['self']->type());
    assertEquals(types::static(resolvedClass: 'A'), $reflection->properties()['static']->type());
};
