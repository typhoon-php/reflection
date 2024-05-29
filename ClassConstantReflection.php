<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\ClassConstantId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\NativeAdapter\ClassConstantAdapter;
use Typhoon\Reflection\Internal\Visibility;
use Typhoon\Type\Type;
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

    /**
     * @var AttributeReflection[]
     * @psalm-var ListOf<AttributeReflection>
     * @phpstan-var ListOf<AttributeReflection>
     */
    public readonly ListOf $attributes;

    public function __construct(
        ClassConstantId $id,
        TypedMap $data,
        private readonly Reflector $reflector,
    ) {
        $this->name = $id->name;
        $this->attributes = (new ListOf($data[Data::Attributes]))->map(
            static fn(TypedMap $data): AttributeReflection => new AttributeReflection($id, $data, $reflector),
        );

        parent::__construct($id, $data);
    }

    /**
     * @return ?non-empty-string
     */
    public function file(): ?string
    {
        if ($this->data[Data::InternallyDefined]) {
            return null;
        }

        return $this->declaringClass()->file();
    }

    public function isInternallyDefined(): bool
    {
        return $this->data[Data::InternallyDefined] || $this->declaringClass()->isInternallyDefined();
    }

    /**
     * @return ?non-empty-string
     */
    public function phpDoc(): ?string
    {
        return $this->data[Data::PhpDoc];
    }

    public function class(): ClassReflection
    {
        return $this->reflector->reflect($this->id->class);
    }

    public function value(): mixed
    {
        // TODO is it correct?
        if ($this->isEnumCase()) {
            return \constant($this->id->class->name . '::' . $this->name);
        }

        return $this->data[Data::ValueExpression]->evaluate($this, $this->reflector);
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

    public function isFinal(Kind $kind = Kind::Resolved): bool
    {
        return match ($kind) {
            Kind::Native => $this->data[Data::NativeFinal],
            Kind::Annotated => $this->data[Data::AnnotatedFinal],
            Kind::Resolved => $this->data[Data::NativeFinal] || $this->data[Data::AnnotatedFinal],
        };
    }

    public function isEnumCase(): bool
    {
        return $this->data[Data::EnumCase];
    }

    /**
     * @psalm-suppress MixedReturnStatement, MixedInferredReturnType
     */
    public function backingValue(): int|string
    {
        $expression = $this->data[Data::EnumBackingValueExpression] ?? throw new \LogicException('Not a backed enum');

        /** @psalm-suppress PossiblyNullReference */
        return $expression->evaluate($this, $this->reflector);
    }

    /**
     * @return ($kind is Kind::Resolved ? Type : ?Type)
     */
    public function type(Kind $kind = Kind::Resolved): ?Type
    {
        return $this->data[Data::Type]->byKind($kind);
    }

    public function toNative(): \ReflectionClassConstant
    {
        return new ClassConstantAdapter($this, $this->reflector);
    }

    private function declaringClass(): ClassReflection
    {
        return $this->reflector->reflect($this->data[Data::DeclaringClassId]);
    }
}
