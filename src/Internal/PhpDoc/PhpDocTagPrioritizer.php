<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpDoc;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\PhpDoc
 */
interface PhpDocTagPrioritizer
{
    /**
     * @param non-empty-string $tagName tag name including @
     * @return int the higher the number, the earlier given tag will be considered
     */
    public function priorityFor(string $tagName): int;
}
