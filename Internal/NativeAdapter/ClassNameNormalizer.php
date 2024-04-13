<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ClassNameNormalizer
{
    private function __construct() {}

    public static function normalize(string $class): string
    {
        $nullBytePosition = strpos($class, "\0");

        if ($nullBytePosition === false) {
            return $class;
        }

        return substr($class, 0, $nullBytePosition);
    }
}
