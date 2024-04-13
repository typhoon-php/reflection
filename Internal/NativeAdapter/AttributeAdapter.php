<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassConstantId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\DeclarationId\PropertyId;
use Typhoon\Reflection\AttributeReflection;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template TAttribute of object
 * @extends \ReflectionAttribute<TAttribute>
 */
final class AttributeAdapter extends \ReflectionAttribute
{
    public function __construct(
        private readonly AttributeReflection $reflection,
    ) {}

    /**
     * @param list<AttributeReflection> $attributes
     * @return list<\ReflectionAttribute>
     */
    public static function from(array $attributes, ?string $name = null, int $flags = 0): array
    {
        if ($name !== null) {
            if ($flags & \ReflectionAttribute::IS_INSTANCEOF) {
                $attributes = array_values(array_filter(
                    $attributes,
                    static fn(AttributeReflection $attribute): bool => $attribute->class()->isInstanceOf($name),
                ));
            } else {
                $attributes = array_values(array_filter(
                    $attributes,
                    static fn(AttributeReflection $attribute): bool => $attribute->className() === $name,
                ));
            }
        }

        return array_map(
            static fn(AttributeReflection $attribute): \ReflectionAttribute => $attribute->toNative(),
            $attributes,
        );
    }

    public function __toString(): string
    {
        // TODO
        return '';
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
        /** @psalm-suppress ParadoxicalCondition */
        return match ($this->reflection->declaredAt::class) {
            FunctionId::class => \Attribute::TARGET_FUNCTION,
            ParameterId::class => \Attribute::TARGET_PARAMETER,
            ClassId::class, AnonymousClassId::class => \Attribute::TARGET_CLASS,
            ClassConstantId::class => \Attribute::TARGET_CLASS_CONSTANT,
            PropertyId::class => \Attribute::TARGET_PROPERTY,
            MethodId::class => \Attribute::TARGET_METHOD,
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
