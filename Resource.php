<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 */
final class Resource
{
    /**
     * @param list<ReflectionHook> $hooks
     */
    public function __construct(
        public readonly string $file,
        public readonly TypedMap $baseData = new TypedMap(),
        public readonly array $hooks = [],
    ) {}
}
