<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ClassInheritance;

use Typhoon\Reflection\Internal\Data;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\ClassInheritance
 */
final class TypeInheritanceResolver
{
    private ?TypedMap $own = null;

    /**
     * @var list<array{TypedMap, TypeProcessor}>
     */
    private array $inherited = [];

    private static function typesEqual(?Type $a, ?Type $b): bool
    {
        // Comparison operator == is intentionally used here.
        // Of course, we need a proper type comparator,
        // but for now simple equality check should do the job 90% of the time.
        return $a == $b;
    }

    public function setOwn(TypedMap $data): void
    {
        $this->own = $data;
    }

    public function addInherited(TypedMap $data, TypeProcessor $typeProcessor): void
    {
        $this->inherited[] = [$data, $typeProcessor];
    }

    public function resolve(): Type
    {
        if ($this->own !== null) {
            $ownAnnotated = $this->own[Data::AnnotatedType()] ?? null;

            if ($ownAnnotated !== null) {
                return $ownAnnotated;
            }

            $ownNative = $this->own[Data::TentativeType()] ?? $this->own[Data::NativeType()];

            foreach ($this->inherited as [$inheritedData, $typeProcessor]) {
                // If own type is different (weakened parameter type or strengthened return type), we want to keep it.
                // This should be compared according to variance with a proper type comparator,
                // but for now simple inequality check should do the job 90% of the time.
                if (!self::typesEqual($inheritedData[Data::NativeType()] ?? null, $ownNative)) {
                    continue;
                }

                $inheritedResolved = $inheritedData[Data::ResolvedType()] ?? null;

                // If inherited type resolves to same native type, we should continue to look for something more interesting.
                if ($inheritedResolved === null || self::typesEqual($inheritedResolved, $ownNative)) {
                    continue;
                }

                return $typeProcessor->process($inheritedResolved);
            }

            return $ownNative ?? types::mixed;
        }

        \assert($this->inherited !== []);

        if (\count($this->inherited) !== 1) {
            foreach ($this->inherited as [$inheritedData, $typeProcessor]) {
                $inheritedNative = $inheritedData[Data::NativeType()] ?? null;
                $inheritedResolved = $inheritedData[Data::ResolvedType()] ?? null;

                // If inherited type resolves to its native type, we should continue to look for something more interesting.
                if ($inheritedResolved === null || self::typesEqual($inheritedNative, $inheritedResolved)) {
                    continue;
                }

                return $typeProcessor->process($inheritedResolved);
            }
        }

        [$inheritedData, $typeProcessor] = $this->inherited[0];

        return $typeProcessor->process($inheritedData[Data::ResolvedType()] ?? types::mixed);
    }
}
