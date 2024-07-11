<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassConstantId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\DeclarationId\PropertyId;
use Typhoon\Reflection\AttributeReflection;
use Typhoon\Reflection\ListOf;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template TAttribute of object
 * @extends \ReflectionAttribute<TAttribute>
 */
final class AttributeAdapter extends \ReflectionAttribute
{
    /**
     * @param non-negative-int $index
     */
    public function __construct(
        private readonly AttributeReflection $reflection,
        private readonly int $index,
    ) {}

    /**
     * @param ?non-empty-string $name
     * @param ListOf<AttributeReflection> $attributes
     * @return list<\ReflectionAttribute>
     */
    public static function fromList(ListOf $attributes, ?string $name, int $flags): array
    {
        if ($name !== null) {
            if ($flags & \ReflectionAttribute::IS_INSTANCEOF) {
                $attributes = $attributes->filter(
                    static fn(AttributeReflection $attribute): bool => $attribute->className() === $name,
                );
            } else {
                $attributes = $attributes->filter(
                    static fn(AttributeReflection $attribute): bool => $attribute->class()->isInstanceOf($name),
                );
            }
        }

        return $attributes
            ->map(static fn(AttributeReflection $attribute): \ReflectionAttribute => $attribute->toNative())
            ->toList();
    }

    public function __toString(): string
    {
        return (string) $this->reflection->targetId->reflect()->getAttributes()[$this->index];
    }

    public function getArguments(): array
    {
        return $this->reflection->arguments();
    }

    public function getName(): string
    {
        return $this->reflection->className();
    }

    public function getTarget(): int
    {
        return match (true) {
            $this->reflection->targetId instanceof FunctionId => \Attribute::TARGET_FUNCTION,
            $this->reflection->targetId instanceof ParameterId => \Attribute::TARGET_PARAMETER,
            $this->reflection->targetId instanceof NamedClassId => \Attribute::TARGET_CLASS,
            $this->reflection->targetId instanceof AnonymousClassId => \Attribute::TARGET_CLASS,
            $this->reflection->targetId instanceof ClassConstantId => \Attribute::TARGET_CLASS_CONSTANT,
            $this->reflection->targetId instanceof PropertyId => \Attribute::TARGET_PROPERTY,
            $this->reflection->targetId instanceof MethodId => \Attribute::TARGET_METHOD,
        };
    }

    public function isRepeated(): bool
    {
        return $this->reflection->isRepeated();
    }

    /**
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     */
    public function newInstance(): object
    {
        return $this->reflection->newInstance();
    }
}
