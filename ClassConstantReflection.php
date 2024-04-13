<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\ClassConstantId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\NativeAdapter\ClassConstantAdapter;
use Typhoon\Reflection\Internal\Visibility;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 * @readonly
 * @extends Reflection<ClassConstantId>
 */
final class ClassConstantReflection extends Reflection
{
    /**
     * @var non-empty-string
     */
    public readonly string $name;

    public function __construct(ClassConstantId $id, TypedMap $data, Reflector $reflector)
    {
        $this->name = $id->name;

        parent::__construct($id, $data, $reflector);
    }

    public function class(): ClassReflection
    {
        return $this->reflector->reflect($this->id->class);
    }

    public function declaringClass(): ClassReflection
    {
        return $this->reflector->reflect($this->declarationId()->class);
    }

    public function value(): mixed
    {
        return $this->data[Data::ValueExpression()]->evaluate($this->reflector);
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

    public function isFinal(Kind $kind = Kind::Resolved): bool
    {
        return match ($kind) {
            Kind::Native => $this->data[Data::NativeFinal()] ?? false,
            Kind::Annotated => $this->data[Data::AnnotatedFinal()] ?? false,
            Kind::Resolved => $this->data[Data::NativeFinal()] ?? $this->data[Data::AnnotatedFinal()] ?? false,
        };
    }

    public function isEnumCase(): bool
    {
        return $this->data[Data::EnumCase()] ?? false;
    }

    /**
     * @psalm-suppress MixedReturnStatement, MixedInferredReturnType
     */
    public function backingValue(): int|string
    {
        return $this->data[Data::BackingValueExpression()]->evaluate($this->reflector);
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

    public function toNative(): \ReflectionClassConstant
    {
        return new ClassConstantAdapter($this);
    }
}
