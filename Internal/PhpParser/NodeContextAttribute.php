<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParser;

use PhpParser\Node\Stmt\ClassMethod;
use Typhoon\Reflection\Internal\Context\Context;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class NodeContextAttribute
{
    private function __construct() {}

    public static function get(ClassMethod $node): Context
    {
        $context = $node->getAttribute(Context::class);
        \assert($context instanceof Context);

        return $context;
    }

    public static function set(ClassMethod $node, Context $context): void
    {
        $node->setAttribute(Context::class, $context);
    }
}
