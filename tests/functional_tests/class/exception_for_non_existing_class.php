<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PHPUnit\Framework\TestCase;
use Typhoon\DeclarationId\Id;
use Typhoon\Reflection\Exception\DeclarationNotFound;

return static function (TyphoonReflector $reflector, TestCase $test): void {
    $class = 'NonExistingClass';

    $test->expectExceptionObject(new DeclarationNotFound(Id::namedClass($class)));

    $reflector->reflectClass($class);
};
