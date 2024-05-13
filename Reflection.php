<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\ChangeDetector\IfSerializedChangeDetector;
use Typhoon\DeclarationId\DeclarationId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 * @template-covariant TId of DeclarationId
 */
abstract class Reflection
{
    /**
     * @var ?list<AttributeReflection>
     */
    private ?array $attributes = null;

    /**
     * @param TId $id
     */
    public function __construct(
        public readonly DeclarationId $id,
        public readonly TypedMap $data,
        protected readonly Reflector $reflector,
    ) {}

    /**
     * @return list<AttributeReflection>
     */
    final public function attributes(): array
    {
        return $this->attributes ??= array_map(
            fn(TypedMap $data): AttributeReflection => new AttributeReflection(
                targetId: $this->id,
                data: $data,
                reflector: $this->reflector,
            ),
            $this->data[Data::Attributes],
        );
    }

    /**
     * @return TId
     */
    final public function declarationId(): DeclarationId
    {
        $declarationId = $this->data[Data::DeclarationId];
        \assert($declarationId instanceof $this->id);

        /** @var TId */
        return $declarationId;
    }

    /**
     * @return ?positive-int
     */
    final public function startLine(): ?int
    {
        return $this->data[Data::StartLine];
    }

    /**
     * @return ?positive-int
     */
    final public function endLine(): ?int
    {
        return $this->data[Data::EndLine];
    }

    /**
     * @return ?non-empty-string
     */
    final public function phpDoc(): ?string
    {
        return $this->data[Data::PhpDoc];
    }

    final public function changeDetector(): ChangeDetector
    {
        return $this->data[Data::ResolvedChangeDetector] ?? new IfSerializedChangeDetector();
    }
}
