<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\PropertyId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\NativeAdapter\PropertyAdapter;
use Typhoon\Reflection\Internal\Visibility;
use Typhoon\Type\Type;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 * @readonly
 * @extends Reflection<PropertyId>
 */
final class PropertyReflection extends Reflection
{
    /**
     * @var non-empty-string
     */
    public readonly string $name;

    /**
     * @var ?list<AttributeReflection>
     */
    private ?array $attributes = null;

    public function __construct(PropertyId $id, TypedMap $data, Reflector $reflector)
    {
        $this->name = $id->name;

        parent::__construct($id, $data, $reflector);
    }

    /**
     * @return list<AttributeReflection>
     */
    public function attributes(): array
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
     * @return ?non-empty-string
     */
    public function phpDoc(): ?string
    {
        return $this->data[Data::PhpDoc];
    }

    public function file(): ?string
    {
        if ($this->data[Data::WrittenInC]) {
            return null;
        }

        return $this->declaringClass()->file();
    }

    public function class(): ClassReflection
    {
        return $this->reflector->reflect($this->id->class);
    }

    public function declaringClass(): ClassReflection
    {
        return $this->reflector->reflect($this->declarationId()->class);
    }

    public function isStatic(): bool
    {
        return $this->data[Data::Static];
    }

    public function isPromoted(): bool
    {
        return $this->data[Data::Promoted];
    }

    public function defaultValue(): mixed
    {
        return $this->data[Data::DefaultValueExpression]?->evaluate($this, $this->reflector);
    }

    public function hasDefaultValue(): bool
    {
        return $this->data[Data::DefaultValueExpression] !== null;
    }

    public function isPrivate(): bool
    {
        return $this->data[Data::Visibility] === Visibility::Private;
    }

    public function isProtected(): bool
    {
        return $this->data[Data::Visibility] === Visibility::Protected;
    }

    public function isPublic(): bool
    {
        $visibility = $this->data[Data::Visibility];

        return $visibility === null || $visibility === Visibility::Public;
    }

    public function isReadonly(Kind $kind = Kind::Resolved): bool
    {
        return match ($kind) {
            Kind::Native => $this->data[Data::NativeReadonly],
            Kind::Annotated => $this->data[Data::AnnotatedReadonly],
            Kind::Resolved => $this->data[Data::NativeReadonly] || $this->data[Data::AnnotatedReadonly],
        };
    }

    /**
     * @return ($kind is Kind::Resolved ? Type : ?Type)
     */
    public function type(Kind $kind = Kind::Resolved): ?Type
    {
        return $this->data[Data::Type]->byKind($kind);
    }

    public function toNative(): \ReflectionProperty
    {
        return new PropertyAdapter($this);
    }
}
