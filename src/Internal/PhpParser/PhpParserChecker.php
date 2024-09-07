<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParser;

use PhpParser\Parser;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PhpParserChecker
{
    private function __construct() {}

    public static function isVisitorLeaveReversed(): bool
    {
        return self::is5();
    }

    private static function is5(): bool
    {
        return method_exists(Parser::class, 'getTokens');
    }
}
