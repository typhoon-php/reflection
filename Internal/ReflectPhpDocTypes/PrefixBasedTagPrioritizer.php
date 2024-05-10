<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ReflectPhpDocTypes;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PrefixBasedTagPrioritizer implements TagPrioritizer
{
    /**
     * @param array<non-empty-string, int> $prefixPriorities
     */
    public function __construct(
        private readonly array $prefixPriorities = ['@psalm' => 3, '@phpstan' => 2, '@phan' => 1],
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
