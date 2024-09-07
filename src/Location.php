<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 */
final class Location
{
    /**
     * @param non-negative-int $startPosition
     * @param non-negative-int $endPosition
     * @param positive-int $startLine
     * @param positive-int $endLine
     * @param positive-int $startColumn
     * @param positive-int $endColumn
     */
    public function __construct(
        public readonly int $startPosition,
        public readonly int $endPosition,
        public readonly int $startLine,
        public readonly int $endLine,
        public readonly int $startColumn,
        public readonly int $endColumn,
    ) {}
}
