<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\NativeAdapter\ParameterAdapter;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Type\Type;

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
     * @var ?ListOf<AttributeReflection>
     */
    private ?ListOf $attributes = null;

    public function __construct(
        ParameterId $id,
        TypedMap $data,
        private readonly Reflector $reflector,
    ) {
        $this->name = $id->name;
        parent::__construct($id, $data);
    }

    /**
     * @return non-negative-int
     */
    public function index(): int
    {
        return $this->data[Data::Index];
    }

    /**
     * @return AttributeReflection[]
     * @psalm-return ListOf<AttributeReflection>
     * @phpstan-return ListOf<AttributeReflection>
     */
    public function attributes(): ListOf
    {
        return $this->attributes ??= (new ListOf($this->data[Data::Attributes]))->map(
            fn(TypedMap $data, int $index): AttributeReflection => new AttributeReflection($this->id, $index, $data, $this->reflector),
        );
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
        if ($this->id->function instanceof MethodId) {
            return $this->reflector->reflect($this->id->function->class);
        }

        return null;
    }

    public function hasDefaultValue(): bool
    {
        return $this->data[Data::DefaultValueExpression] !== null;
    }

    public function defaultValue(): mixed
    {
        return $this->data[Data::DefaultValueExpression]?->evaluate($this->reflector);
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
