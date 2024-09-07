<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PHPUnit\Framework\TestCase;
use Typhoon\Reflection\Locator\Resource;

return static function (TyphoonReflector $reflector, TestCase $test): void {
    $test->expectExceptionObject(new \LogicException('Infinite recursive reflection of class A detected'));

    $reflector
        ->withResource(Resource::fromCode('<?php class A extends A {}'))
        ->reflectClass('A');
};
