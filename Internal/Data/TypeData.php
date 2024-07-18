<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Reflection\DeclarationKind;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon
 * @psalm-immutable
 */
final class TypeData
{
    private ?Type $resolved = null;

    public function __construct(
        public ?Type $native = null,
        public ?Type $annotated = null,
        public ?Type $tentative = null,
    ) {}

    public function resolved(): Type
    {
        return $this->resolved ?? $this->annotated ?? $this->tentative ?? $this->native ?? types::mixed;
    }

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

    public function withResolved(?Type $resolved): self
    {
        $data = clone $this;
        $data->resolved = $resolved;

        return $data;
    }

    /**
     * @return ($kind is null ? Type : ?Type)
     */
    public function ofKind(?DeclarationKind $kind = null): ?Type
    {
        return match ($kind) {
            DeclarationKind::Native => $this->native,
            DeclarationKind::Annotated => $this->annotated,
            null => $this->resolved(),
        };
    }
}
