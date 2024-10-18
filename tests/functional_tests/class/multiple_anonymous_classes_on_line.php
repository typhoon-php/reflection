<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PHPUnit\Framework\TestCase;
use Typhoon\Reflection\Locator\Resource;

return static function (TyphoonReflector $reflector, TestCase $test): void {
    $reflector = $reflector->withResource(new Resource(
        code: '<?php new class {}; new class {};',
        file: 'some.php',
    ));

    $test->expectExceptionMessage('because 2 anonymous classes are declared at columns 11, 25');

    $reflector->reflectAnonymousClass('some.php', 1);
};
