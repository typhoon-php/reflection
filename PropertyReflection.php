<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\PropertyId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\NativeAdapter\PropertyAdapter;
use Typhoon\Reflection\Internal\Visibility;
use Typhoon\Type\Type;
use Typhoon\Type\types;
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

    public function __construct(PropertyId $id, TypedMap $data, Reflector $reflector)
    {
        $this->name = $id->name;

        parent::__construct($id, $data, $reflector);
    }

    public function file(): ?string
    {
        if ($this->data[Data::WrittenInC()] ?? false) {
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
        return $this->data[Data::Static()] ?? false;
    }

    public function isPromoted(): bool
    {
        return $this->data[Data::Promoted()] ?? false;
    }

    public function defaultValue(): mixed
    {
        return ($this->data[Data::DefaultValueExpression()] ?? null)?->evaluate($this, $this->reflector);
    }

    public function hasDefaultValue(): bool
    {
        return ($this->data[Data::DefaultValueExpression()] ?? null)  !== null;
    }

    public function isPrivate(): bool
    {
        return $this->data[Data::Visibility()] === Visibility::Private;
    }

    public function isProtected(): bool
    {
        return $this->data[Data::Visibility()] === Visibility::Protected;
    }

    public function isPublic(): bool
    {
        $visibility = $this->data[Data::Visibility()];

        return $visibility === null || $visibility === Visibility::Public;
    }

    public function isReadonly(Kind $kind = Kind::Resolved): bool
    {
        return match ($kind) {
            Kind::Native => $this->data[Data::NativeReadonly()] ?? false,
            Kind::Annotated => $this->data[Data::AnnotatedReadonly()] ?? false,
            Kind::Resolved => $this->data[Data::NativeReadonly()] ?? $this->data[Data::AnnotatedReadonly()] ?? false,
        };
    }

    /**
     * @return ($kind is Kind::Resolved ? Type : ?Type)
     */
    public function type(Kind $kind = Kind::Resolved): ?Type
    {
        return match ($kind) {
            Kind::Native => $this->data[Data::NativeType()] ?? null,
            Kind::Annotated => $this->data[Data::AnnotatedType()] ?? null,
            Kind::Resolved => $this->data[Data::ResolvedType()]
                ?? $this->data[Data::AnnotatedType()]
                ?? $this->data[Data::NativeType()]
                ?? types::mixed,
        };
    }

    public function toNative(): \ReflectionProperty
    {
        return new PropertyAdapter($this);
    }
}
