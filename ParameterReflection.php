<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\FunctionId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\NativeAdapter\ParameterAdapter;
use Typhoon\Type\Type;
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

    /**
     * @var AttributeReflection[]
     * @psalm-var AttributeReflections
     * @phpstan-var AttributeReflections
     */
    public readonly AttributeReflections $attributes;

    public function __construct(ParameterId $id, TypedMap $data, Reflector $reflector)
    {
        $this->name = $id->name;
        $this->index = $data[Data::Index];
        $this->attributes = new AttributeReflections($id, $data[Data::Attributes], $reflector);

        parent::__construct($id, $data, $reflector);
    }

    /**
     * @return ?non-empty-string
     */
    public function phpDoc(): ?string
    {
        return $this->data[Data::PhpDoc];
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
        return $this->data[Data::DefaultValueExpression] !== null;
    }

    public function defaultValue(): mixed
    {
        return $this->data[Data::DefaultValueExpression]?->evaluate($this, $this->reflector);
    }

    public function isOptional(): bool
    {
        return $this->hasDefaultValue() || $this->isVariadic();
    }

    public function isPassedByReference(): bool
    {
        return $this->data[Data::ByReference];
    }

    public function isPromoted(): bool
    {
        return $this->data[Data::Promoted];
    }

    public function isVariadic(): bool
    {
        return $this->data[Data::Variadic];
    }

    /**
     * @return ($kind is Kind::Resolved ? Type : ?Type)
     */
    public function type(Kind $kind = Kind::Resolved): ?Type
    {
        return $this->data[Data::Type]->byKind($kind);
    }

    public function toNative(): \ReflectionParameter
    {
        return new ParameterAdapter($this, $this->reflector);
    }
}
