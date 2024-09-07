<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PHPUnit\Framework\TestCase;
use Typhoon\DeclarationId\Id;
use Typhoon\Reflection\Exception\FileIsNotReadable;

return static function (TyphoonReflector $reflector, TestCase $test): void {
    $file = __FILE__ . '.fake';
    $id = Id::anonymousClass($file, 10, 20);

    $test->expectExceptionObject(new FileIsNotReadable($file));

    $reflector->reflect($id);
};
