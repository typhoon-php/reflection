<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Reflection\Kind;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon
 */
final class TypeData
{
    public function __construct(
        public readonly ?Type $native = null,
        public readonly ?Type $annotated = null,
        public readonly ?Type $tentative = null,
        private readonly ?Type $resolved = null,
    ) {}

    public function resolved(): Type
    {
        return $this->resolved ?? $this->annotated ?? $this->tentative ?? $this->native ?? types::mixed;
    }

    public function withNative(?Type $native): self
    {
        return new self(
            native: $native,
            annotated: $this->annotated,
            tentative: $this->tentative,
            resolved: $this->resolved,
        );
    }

    public function withTentative(?Type $tentative): self
    {
        return new self(
            native: $this->native,
            annotated: $this->annotated,
            tentative: $tentative,
            resolved: $this->resolved,
        );
    }

    public function withAnnotated(?Type $annotated): self
    {
        return new self(
            native: $this->native,
            annotated: $annotated,
            tentative: $this->tentative,
            resolved: $this->resolved,
        );
    }

    public function withResolved(?Type $resolved): self
    {
        return new self(
            native: $this->native,
            annotated: $this->annotated,
            tentative: $this->tentative,
            resolved: $resolved,
        );
    }

    /**
     * @return ($kind is Kind::Resolved ? Type : ?Type)
     */
    public function byKind(Kind $kind = Kind::Resolved): ?Type
    {
        return match ($kind) {
            Kind::Native => $this->native,
            Kind::Annotated => $this->annotated,
            Kind::Resolved => $this->resolved(),
        };
    }
}
