<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Inheritance;

use Typhoon\Reflection\Internal\Data\TypeData;
use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\Inheritance
 */
final class TypeInheritance
{
    private ?TypeData $own = null;

    /**
     * @var list<array{TypeData, TypeResolver}>
     */
    private array $inherited = [];

    private static function typesEqual(?Type $a, ?Type $b): bool
    {
        // Comparison operator == is intentionally used here.
        // Of course, we need a proper type comparator,
        // but for now simple equality check should do the job 90% of the time.
        return $a == $b;
    }

    public function applyOwn(TypeData $data): void
    {
        $this->own = $data;
    }

    public function applyInherited(TypeData $data, TypeResolver $typeResolver): void
    {
        $this->inherited[] = [$data, $typeResolver];
    }

    public function build(): TypeData
    {
        if ($this->own !== null) {
            if ($this->own->annotated !== null) {
                return $this->own;
            }

            foreach ($this->inherited as [$inherited, $typeResolver]) {
                // If own type is different (weakened parameter type or strengthened return type), we want to keep it.
                // This should be compared according to variance with a proper type comparator,
                // but for now simple inequality check should do the job 90% of the time.
                if (!self::typesEqual($inherited->native, $this->own->native)) {
                    continue;
                }

                // If inherited type resolves to same native type, we should continue to look for something more interesting.
                if (self::typesEqual($inherited->get(), $this->own->native)) {
                    continue;
                }

                return $inherited->withTentative(null)->inherit($typeResolver);
            }

            return $this->own;
        }

        \assert($this->inherited !== []);

        if (\count($this->inherited) !== 1) {
            foreach ($this->inherited as [$inherited, $typeResolver]) {
                // If inherited type resolves to its native type, we should continue to look for something more interesting.
                if (self::typesEqual($inherited->get(), $inherited->native)) {
                    continue;
                }

                return $inherited->inherit($typeResolver);
            }
        }

        [$inherited, $typeResolver] = $this->inherited[0];

        return $inherited->inherit($typeResolver);
    }
}
