<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpDoc;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\PhpDoc
 */
final class PrefixBasedPhpDocTagPrioritizer implements PhpDocTagPrioritizer
{
    public const DEFAULT_PREFIX_PRIORITIES = [
        '@psalm' => 3,
        '@phpstan' => 2,
        '@phan' => 1,
    ];

    /**
     * @param array<non-empty-string, int> $prefixPriorities
     */
    public function __construct(
        private readonly array $prefixPriorities = self::DEFAULT_PREFIX_PRIORITIES,
    ) {}

    public function priorityFor(string $tagName): int
    {
        foreach ($this->prefixPriorities as $prefix => $priority) {
            if (str_starts_with($tagName, $prefix)) {
                return $priority;
            }
        }

        return 0;
    }
}
