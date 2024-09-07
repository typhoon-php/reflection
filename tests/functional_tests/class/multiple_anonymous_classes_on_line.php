<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PHPUnit\Framework\TestCase;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Locator\Resource;
use Typhoon\TypedMap\TypedMap;

return static function (TyphoonReflector $reflector, TestCase $test): void {
    $reflector = $reflector->withResource(Resource::fromCode(
        '<?php new class {}; new class {};',
        (new TypedMap())->with(Data::File, 'some.php'),
    ));

    $test->expectExceptionMessage('because 2 anonymous classes are declared at columns 11, 25');

    $reflector->reflectAnonymousClass('some.php', 1);
};
