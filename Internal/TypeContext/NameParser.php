<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\TypeContext;

use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Name\Relative;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class NameParser
{
    private function __construct() {}

    public static function parse(string $name): Name
    {
        if ($name === '') {
            throw new \LogicException('Name cannot be empty');
        }

        if ($name[0] === '\\') {
            return new FullyQualified(substr($name, 1));
        }

        if (str_starts_with($name, 'namespace\\')) {
            return new Relative(substr($name, 10));
        }

        return new Name($name);
    }
}
