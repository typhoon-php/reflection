<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Expression\ClassConstantFetch;
use Typhoon\Reflection\Internal\Expression\ConstantFetch;
use Typhoon\Reflection\Kind;
use Typhoon\Reflection\ParameterReflection;
use Typhoon\Reflection\Reflector;
use Typhoon\Type\Type;
use Typhoon\Type\Visitor\DefaultTypeVisitor;
use function Typhoon\DeclarationId\anyClassId;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @property-read non-empty-string $name
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ParameterAdapter extends \ReflectionParameter
{
    private bool $nativeLoaded = false;

    public function __construct(
        private readonly ParameterReflection $reflection,
        private readonly Reflector $reflector,
    ) {
        unset($this->name);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'name' => $this->reflection->name,
            default => new \LogicException(sprintf('Undefined property %s::$%s', self::class, $name)),
        };
    }

    public function __isset(string $name): bool
    {
        return $name === 'name';
    }

    public function __toString(): string
    {
        $this->loadNative();

        return parent::__toString();
    }

    public function allowsNull(): bool
    {
        return $this->reflection->type(Kind::Native)?->accept(
            new /** @extends DefaultTypeVisitor<bool> */ class () extends DefaultTypeVisitor {
                public function null(Type $self): mixed
                {
                    return true;
                }

                public function union(Type $self, array $types): mixed
                {
                    foreach ($types as $type) {
                        if ($type->accept($this)) {
                            return true;
                        }
                    }

                    return false;
                }

                public function mixed(Type $self): mixed
                {
                    return true;
                }

                protected function default(Type $self): mixed
                {
                    return false;
                }
            },
        ) ?? true;
    }

    public function canBePassedByValue(): bool
    {
        return !$this->reflection->isPassedByReference();
    }

    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        return AttributeAdapter::from($this->reflection->attributes, $name, $flags);
    }

    public function getClass(): ?\ReflectionClass
    {
        return $this->reflection->type(Kind::Native)?->accept(
            new /** @extends DefaultTypeVisitor<?ClassReflection> */ class ($this->reflection, $this->reflector) extends DefaultTypeVisitor {
                public function __construct(
                    private readonly ParameterReflection $reflection,
                    private readonly Reflector $reflector,
                ) {}

                public function namedObject(Type $self, ClassId|AnonymousClassId $class, array $arguments): mixed
                {
                    return $this->reflector->reflect($class);
                }

                public function self(Type $self, null|ClassId|AnonymousClassId $resolvedClass, array $arguments): mixed
                {
                    return $this->reflection->class();
                }

                public function parent(Type $self, ?ClassId $resolvedClass, array $arguments): mixed
                {
                    return $this->reflection->class()?->parent();
                }

                public function closure(Type $self, array $parameters, Type $return): mixed
                {
                    return $this->reflector->reflect(anyClassId(\Closure::class));
                }

                protected function default(Type $self): mixed
                {
                    return null;
                }
            },
        )?->toNative();
    }

    public function getDeclaringClass(): ?\ReflectionClass
    {
        $declarationId = $this->reflection->data[Data::DeclarationId];
        \assert($declarationId instanceof ParameterId);

        if ($declarationId->function instanceof FunctionId) {
            return null;
        }

        $declaringClass = $this->reflector->reflect($declarationId->function->class);

        if ($declaringClass->isTrait()) {
            return $this->reflection->class()?->toNative();
        }

        return $declaringClass->toNative();
    }

    public function getDeclaringFunction(): \ReflectionFunctionAbstract
    {
        $function = $this->reflection->declaringFunction();

        if ($function->class()->isTrait()) {
            return $this->reflection->function()->toNative();
        }

        return $function->toNative();
    }

    public function getDefaultValue(): mixed
    {
        return $this->reflection->defaultValue();
    }

    public function getDefaultValueConstantName(): ?string
    {
        $expression = $this->reflection->data[Data::DefaultValueExpression];

        if ($expression instanceof ConstantFetch) {
            return $expression->name($this->reflector);
        }

        if ($expression instanceof ClassConstantFetch) {
            return $expression->name($this->reflection, $this->reflector);
        }

        return null;
    }

    public function getName(): string
    {
        return $this->reflection->name;
    }

    public function getPosition(): int
    {
        return $this->reflection->index;
    }

    public function getType(): ?\ReflectionType
    {
        return $this->reflection->type(Kind::Native)?->accept(new ToNativeTypeConverter());
    }

    public function hasType(): bool
    {
        return $this->reflection->type(Kind::Native) !== null;
    }

    public function isArray(): bool
    {
        return $this->reflection->type(Kind::Native)?->accept(
            new /** @extends DefaultTypeVisitor<bool> */ class () extends DefaultTypeVisitor {
                public function array(Type $self, Type $key, Type $value, array $elements): mixed
                {
                    return true;
                }

                protected function default(Type $self): mixed
                {
                    return false;
                }
            },
        ) ?? false;
    }

    public function isCallable(): bool
    {
        return $this->reflection->type(Kind::Native)?->accept(
            new /** @extends DefaultTypeVisitor<bool> */ class () extends DefaultTypeVisitor {
                public function callable(Type $self, array $parameters, Type $return): mixed
                {
                    return true;
                }

                protected function default(Type $self): mixed
                {
                    return false;
                }
            },
        ) ?? false;
    }

    public function isDefaultValueAvailable(): bool
    {
        return $this->reflection->hasDefaultValue();
    }

    public function isDefaultValueConstant(): bool
    {
        $expression = $this->reflection->data[Data::DefaultValueExpression];

        return $expression instanceof ConstantFetch || $expression instanceof ClassConstantFetch;
    }

    public function isOptional(): bool
    {
        return $this->reflection->isOptional();
    }

    public function isPassedByReference(): bool
    {
        return $this->reflection->isPassedByReference();
    }

    public function isPromoted(): bool
    {
        return $this->reflection->isPromoted();
    }

    public function isVariadic(): bool
    {
        return $this->reflection->isVariadic();
    }

    private function loadNative(): void
    {
        if (!$this->nativeLoaded) {
            $functionId = $this->reflection->id->function;

            if ($functionId instanceof FunctionId) {
                parent::__construct($functionId->name, $this->name);
            } else {
                parent::__construct([$functionId->class->name, $functionId->name], $this->name);
            }

            $this->nativeLoaded = true;
        }
    }
}
