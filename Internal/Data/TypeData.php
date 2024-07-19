<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Reflection\DeclarationKind;
use Typhoon\Reflection\Internal\Inheritance\TypeResolver;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon
 */
final class TypeData
{
    public function __construct(
        public ?Type $native = null,
        public ?Type $annotated = null,
        public ?Type $tentative = null,
    ) {}

    public function withNative(?Type $native): self
    {
        $data = clone $this;
        $data->native = $native;

        return $data;
    }

    public function withAnnotated(?Type $annotated): self
    {
        $data = clone $this;
        $data->annotated = $annotated;

        return $data;
    }

    public function withTentative(?Type $tentative): self
    {
        $data = clone $this;
        $data->tentative = $tentative;

        return $data;
    }

    public function inherit(TypeResolver $typeResolver): self
    {
        return new self(
            native: $typeResolver->resolveNativeType($this->native),
            annotated: $typeResolver->resolveType($this->annotated),
            tentative: $typeResolver->resolveNativeType($this->tentative),
        );
    }

    public function get(?DeclarationKind $kind = null): ?Type
    {
        return match ($kind) {
            null => $this->annotated ?? $this->tentative ?? $this->native ?? types::mixed,
            DeclarationKind::Native => $this->native,
            DeclarationKind::Annotated => $this->annotated,
        };
    }
}
