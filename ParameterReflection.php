<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\FunctionId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\NativeAdapter\ParameterAdapter;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 * @readonly
 * @extends Reflection<ParameterId>
 */
final class ParameterReflection extends Reflection
{
    /**
     * @var non-empty-string
     */
    public readonly string $name;

    /**
     * @var non-negative-int
     */
    public readonly int $index;

    public function __construct(ParameterId $id, TypedMap $data, Reflector $reflector)
    {
        $this->name = $id->name;
        $this->index = $data[Data::Index()];

        parent::__construct($id, $data, $reflector);
    }

    public function function(): MethodReflection
    {
        return $this->reflector->reflect($this->id->function);
    }

    public function class(): ?ClassReflection
    {
        if ($this->id->function instanceof FunctionId) {
            return null;
        }

        return $this->reflector->reflect($this->id->function->class);
    }

    public function declaringFunction(): MethodReflection
    {
        return $this->reflector->reflect($this->declarationId()->function);
    }

    public function declaringClass(): ?ClassReflection
    {
        $declarationId = $this->declarationId();

        if ($declarationId->function instanceof FunctionId) {
            return null;
        }

        return $this->reflector->reflect($declarationId->function->class);
    }

    public function hasDefaultValue(): bool
    {
        return ($this->data[Data::DefaultValueExpression()] ?? null) !== null;
    }

    public function defaultValue(): mixed
    {
        return ($this->data[Data::DefaultValueExpression()] ?? null)?->evaluate($this->reflector);
    }

    public function isOptional(): bool
    {
        return $this->hasDefaultValue() || $this->isVariadic();
    }

    public function isPassedByReference(): bool
    {
        return $this->data[Data::ByReference()] ?? false;
    }

    public function isPromoted(): bool
    {
        return $this->data[Data::Promoted()] ?? false;
    }

    public function isVariadic(): bool
    {
        return $this->data[Data::Variadic()] ?? false;
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

    public function toNative(): \ReflectionParameter
    {
        return new ParameterAdapter($this, $this->reflector);
    }
}
