<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use Typhoon\Type\types;
use function PHPUnit\Framework\assertEquals;

return static function (TyphoonReflector $reflector): void {
    $reflector = $reflector->withResource(Resource::fromCode(
        <<<'PHP'
            <?php

            final class A
            {
                /**
                 * @param non-empty-string $onlyParam
                 * @param scalar $paramAndVar
                 */
                public function __construct(
                    public $onlyParam,
                    /**
                     * @var positive-int
                     */
                    public $onlyVar,
                    /**
                     * @var class-string
                     */
                    public $paramAndVar,
                ) {}
            }

            final class B
            {
                public function __construct(
                    /** @var positive-int */
                    public $noMethodPhpDocProperty,
                ) {}
            }
            PHP,
    ));

    $classA = $reflector->reflectClass('A');
    $constructor = $classA->methods()['__construct'];

    assertEquals(types::nonEmptyString, $classA->properties()['onlyParam']->type());
    assertEquals(types::nonEmptyString, $constructor->parameters()['onlyParam']->type());
    assertEquals(types::positiveInt, $classA->properties()['onlyVar']->type());
    assertEquals(types::positiveInt, $constructor->parameters()['onlyVar']->type());
    assertEquals(types::classString, $classA->properties()['paramAndVar']->type());
    assertEquals(types::classString, $constructor->parameters()['paramAndVar']->type());

    assertEquals(types::positiveInt, $reflector->reflectClass('B')->properties()['noMethodPhpDocProperty']->type());
};
