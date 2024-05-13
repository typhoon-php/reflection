<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ResolveClassInheritance;

use Typhoon\Reflection\Internal\TypeData;
use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class TypeInheritanceResolver
{
    private ?TypeData $own = null;

    /**
     * @var list<array{TypeData, TypeProcessor}>
     */
    private array $inherited = [];

    private static function typesEqual(?Type $a, ?Type $b): bool
    {
        // Comparison operator == is intentionally used here.
        // Of course, we need a proper type comparator,
        // but for now simple equality check should do the job 90% of the time.
        return $a == $b;
    }

    public function setOwn(TypeData $data): void
    {
        $this->own = $data;
    }

    public function addInherited(TypeData $data, TypeProcessor $typeProcessor): void
    {
        $this->inherited[] = [$data, $typeProcessor];
    }

    public function resolve(): TypeData
    {
        if ($this->own !== null) {
            if ($this->own->annotated !== null) {
                return $this->own;
            }

            foreach ($this->inherited as [$inherited, $typeProcessor]) {
                // If own type is different (weakened parameter type or strengthened return type), we want to keep it.
                // This should be compared according to variance with a proper type comparator,
                // but for now simple inequality check should do the job 90% of the time.
                if (!self::typesEqual($inherited->native, $this->own->native)) {
                    continue;
                }

                // If inherited type resolves to same native type, we should continue to look for something more interesting.
                if (self::typesEqual($inherited->resolved(), $this->own->native)) {
                    continue;
                }

                return $inherited->withResolved($typeProcessor->process($inherited->resolved()));
            }

            return $this->own;
        }

        \assert($this->inherited !== []);

        if (\count($this->inherited) !== 1) {
            foreach ($this->inherited as [$inherited, $typeProcessor]) {
                // If inherited type resolves to its native type, we should continue to look for something more interesting.
                if (self::typesEqual($inherited->resolved(), $inherited->native)) {
                    continue;
                }

                return $inherited->withResolved($typeProcessor->process($inherited->resolved()));
            }
        }

        [$inherited, $typeProcessor] = $this->inherited[0];

        return $inherited->withResolved($typeProcessor->process($inherited->resolved()));
    }
}
