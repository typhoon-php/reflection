<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\Reflection\Internal\Data\TraitMethodAlias;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class NativeTraitInfo
{
    /**
     * @var array<non-empty-string, non-empty-string>
     */
    public readonly array $aliases;

    /**
     * @param list<non-empty-string> $names
     * @param list<TraitMethodAlias> $aliases
     */
    public function __construct(
        public readonly array $names = [],
        array $aliases = [],
    ) {
        $resolvedAliases = [];

        foreach ($aliases as $alias) {
            if ($alias->newName !== null) {
                $resolvedAliases[$alias->newName] = $alias->trait . '::' . $alias->method;
            }
        }

        $this->aliases = $resolvedAliases;
    }
}
