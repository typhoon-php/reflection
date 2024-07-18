<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\Internal\ConstantExpression\ClassConstantFetch;
use Typhoon\Reflection\Internal\ConstantExpression\ConstantFetch;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Kind;
use Typhoon\Reflection\ParameterReflection;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Type\Type;
use Typhoon\Type\Visitor\DefaultTypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @property-read non-empty-string $name
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ParameterAdapter extends \ReflectionParameter
{
    public function __construct(
        private readonly ParameterReflection $reflection,
        private readonly TyphoonReflector $reflector,
    ) {
        unset($this->name);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'name' => $this->reflection->id->name,
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
                public function null(Type $type): mixed
                {
                    return true;
                }

                public function union(Type $type, array $ofTypes): mixed
                {
                    foreach ($ofTypes as $ofType) {
                        if ($ofType->accept($this)) {
                            return true;
                        }
                    }

                    return false;
                }

                public function mixed(Type $type): mixed
                {
                    return true;
                }

                protected function default(Type $type): mixed
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
        return AttributeAdapter::fromList($this->reflection->attributes(), $name, $flags);
    }

    public function getClass(): ?\ReflectionClass
    {
        return $this->reflection->type(Kind::Native)?->accept(
            new /** @extends DefaultTypeVisitor<?ClassReflection> */ class ($this->reflection, $this->reflector) extends DefaultTypeVisitor {
                public function __construct(
                    private readonly ParameterReflection $reflection,
                    private readonly TyphoonReflector $reflector,
                ) {}

                public function namedObject(Type $type, NamedClassId $class, array $typeArguments): mixed
                {
                    return $this->reflector->reflect($class);
                }

                public function self(Type $type, array $typeArguments, null|NamedClassId|AnonymousClassId $resolvedClass): mixed
                {
                    return $this->reflection->class();
                }

                public function parent(Type $type, array $typeArguments, ?NamedClassId $resolvedClass): mixed
                {
                    return $this->reflection->class()?->parent();
                }

                protected function default(Type $type): mixed
                {
                    return null;
                }
            },
        )?->native();
    }

    public function getDeclaringClass(): ?\ReflectionClass
    {
        $declaringFunction = $this->getDeclaringFunction();

        if ($declaringFunction instanceof \ReflectionMethod) {
            return $declaringFunction->getDeclaringClass();
        }

        return null;
    }

    public function getDeclaringFunction(): \ReflectionFunctionAbstract
    {
        return $this->reflection->function()->native();
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
            $functionId = $this->reflection->id->function;
            $expressionClass = $expression->class($this->reflector);

            if ($functionId instanceof MethodId && $expressionClass === $functionId->class->name) {
                $expressionClass = 'self';
            }

            return $expressionClass . '::' . $expression->name($this->reflector);
        }

        return null;
    }

    public function getName(): string
    {
        return $this->reflection->id->name;
    }

    public function getPosition(): int
    {
        return $this->reflection->index();
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
                public function array(Type $type, Type $keyType, Type $valueType, array $elements): mixed
                {
                    return true;
                }

                protected function default(Type $type): mixed
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
                public function callable(Type $type, array $parameters, Type $returnType): mixed
                {
                    return true;
                }

                protected function default(Type $type): mixed
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

    private bool $nativeLoaded = false;

    private function loadNative(): void
    {
        if ($this->nativeLoaded) {
            return;
        }

        $functionId = $this->reflection->id->function;

        if ($functionId instanceof NamedFunctionId) {
            parent::__construct($functionId->name, $this->name);
            $this->nativeLoaded = true;

            return;
        }

        if ($functionId instanceof AnonymousFunctionId) {
            throw new \LogicException(sprintf('Cannot natively reflect %s', $functionId->describe()));
        }

        $class = $functionId->class->name ?? throw new \LogicException(sprintf(
            "Cannot natively reflect %s, because it's runtime name is not available",
            $functionId->class->describe(),
        ));

        parent::__construct([$class, $functionId->name], $this->name);
        $this->nativeLoaded = true;
    }
}
