<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exception;

/**
 * @api
 */
final class ClassDoesNotExist extends \RuntimeException
{
    public function __construct(string $class)
    {
        parent::__construct(sprintf('Class "%s" does not exist', self::normalizeClassName($class)));
    }

    private static function normalizeClassName(string $class): string
    {
        $nullBytePosition = strpos($class, "\0");

        if ($nullBytePosition === false) {
            return $class;
        }

        return substr($class, 0, $nullBytePosition);
    }
}
