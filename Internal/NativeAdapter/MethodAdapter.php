<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Kind;
use Typhoon\Reflection\MethodReflection;
use Typhoon\Reflection\ParameterReflection;
use Typhoon\Reflection\Reflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @property-read non-empty-string $name
 * @property-read class-string $class
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class MethodAdapter extends \ReflectionMethod
{
    private bool $nativeLoaded = false;

    public function __construct(
        private readonly MethodReflection $reflection,
        private readonly Reflector $reflector,
    ) {
        unset($this->name, $this->class);
    }

    /**
     * @psalm-suppress MethodSignatureMismatch, PossiblyUnusedMethod, UnusedPsalmSuppress, UnusedParam
     */
    public static function createFromMethodName(string $method): never
    {
        throw new \BadMethodCallException(sprintf('%s must never be called', __METHOD__));
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __get(string $name)
    {
        return match ($name) {
            'name' => $this->reflection->name,
            'class' => $this->getDeclaringClass()->name,
            default => new \LogicException(sprintf('Undefined property %s::$%s', self::class, $name)),
        };
    }

    public function __isset(string $name): bool
    {
        return $name === 'name' || $name === 'class';
    }

    public function __toString(): string
    {
        $this->loadNative();

        return parent::__toString();
    }

    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        return AttributeAdapter::fromList($this->reflection->attributes(), $name, $flags);
    }

    public function getClosure(?object $object = null): \Closure
    {
        $this->loadNative();

        return parent::getClosure($object);
    }

    public function getClosureCalledClass(): ?\ReflectionClass
    {
        return null;
    }

    public function getClosureScopeClass(): ?\ReflectionClass
    {
        return null;
    }

    public function getClosureThis(): ?object
    {
        return null;
    }

    public function getClosureUsedVariables(): array
    {
        return [];
    }

    public function getDeclaringClass(): \ReflectionClass
    {
        $declaringClassId = $this->reflection->data[Data::DeclaringClassId];

        if ($declaringClassId->equals($this->reflection->id->class)) {
            $declaringClassId = $this->reflection->id->class;
        }

        $declaringClass = $this->reflector->reflect($declaringClassId);

        if ($declaringClass->isTrait()) {
            return $this->reflection->class()->toNative();
        }

        return $declaringClass->toNative();
    }

    public function getDocComment(): string|false
    {
        return $this->reflection->phpDoc() ?? false;
    }

    public function getEndLine(): int|false
    {
        return $this->reflection->endLine() ?? false;
    }

    public function getExtension(): ?\ReflectionExtension
    {
        return $this->getDeclaringClass()->getExtension();
    }

    public function getExtensionName(): string|false
    {
        return $this->getDeclaringClass()->getExtensionName();
    }

    public function getFileName(): string|false
    {
        return $this->reflection->file() ?? false;
    }

    public function getModifiers(): int
    {
        return ($this->isStatic() ? self::IS_STATIC : 0)
            | ($this->isPublic() ? self::IS_PUBLIC : 0)
            | ($this->isProtected() ? self::IS_PROTECTED : 0)
            | ($this->isPrivate() ? self::IS_PRIVATE : 0)
            | ($this->isAbstract() ? self::IS_ABSTRACT : 0)
            | ($this->isFinal() ? self::IS_FINAL : 0);
    }

    public function getName(): string
    {
        return $this->reflection->name;
    }

    public function getNamespaceName(): string
    {
        return '';
    }

    public function getNumberOfParameters(): int
    {
        return $this->reflection->parameters()->count();
    }

    public function getNumberOfRequiredParameters(): int
    {
        return $this
            ->reflection
            ->parameters()
            ->filter(static fn(ParameterReflection $reflection): bool => !$reflection->isOptional())
            ->count();
    }

    /**
     * @return list<\ReflectionParameter>
     */
    public function getParameters(): array
    {
        return $this
            ->reflection
            ->parameters()
            ->map(static fn(ParameterReflection $parameter): \ReflectionParameter => $parameter->toNative())
            ->toList();
    }

    /**
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function getPrototype(): \ReflectionMethod
    {
        $this->loadNative();

        return parent::getPrototype();
    }

    public function getReturnType(): ?\ReflectionType
    {
        return $this->reflection->returnType(Kind::Native)?->accept(new ToNativeTypeConverter());
    }

    public function getShortName(): string
    {
        return $this->reflection->name;
    }

    public function getStartLine(): int|false
    {
        return $this->reflection->startLine() ?? false;
    }

    public function getStaticVariables(): array
    {
        $this->loadNative();

        return parent::getStaticVariables();
    }

    public function getTentativeReturnType(): ?\ReflectionType
    {
        return $this->reflection->data[Data::Type]->tentative?->accept(new ToNativeTypeConverter());
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, UnusedPsalmSuppress
     */
    public function hasPrototype(): bool
    {
        try {
            $this->getPrototype();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function hasReturnType(): bool
    {
        return $this->reflection->returnType(Kind::Native) !== null;
    }

    public function hasTentativeReturnType(): bool
    {
        return $this->reflection->data[Data::Type]->tentative !== null;
    }

    public function inNamespace(): bool
    {
        return false;
    }

    public function invoke(?object $object = null, mixed ...$args): mixed
    {
        $this->loadNative();

        return parent::invoke($object, ...$args);
    }

    public function invokeArgs(?object $object = null, array $args = []): mixed
    {
        $this->loadNative();

        return parent::invokeArgs($object, $args);
    }

    public function isAbstract(): bool
    {
        return $this->reflection->isAbstract();
    }

    public function isClosure(): bool
    {
        return false;
    }

    public function isConstructor(): bool
    {
        return $this->reflection->name === '__construct';
    }

    public function isDeprecated(): bool
    {
        return false;
    }

    public function isDestructor(): bool
    {
        return $this->reflection->name === '__destruct';
    }

    public function isFinal(): bool
    {
        return $this->reflection->isFinal(Kind::Native);
    }

    public function isGenerator(): bool
    {
        return $this->reflection->isGenerator();
    }

    public function isInternal(): bool
    {
        return $this->reflection->isInternallyDefined();
    }

    public function isPrivate(): bool
    {
        return $this->reflection->isPrivate();
    }

    public function isProtected(): bool
    {
        return $this->reflection->isProtected();
    }

    public function isPublic(): bool
    {
        return $this->reflection->isPublic();
    }

    public function isStatic(): bool
    {
        return $this->reflection->isStatic();
    }

    public function isUserDefined(): bool
    {
        return !$this->isInternal();
    }

    public function isVariadic(): bool
    {
        return $this->reflection->isVariadic();
    }

    public function returnsReference(): bool
    {
        return $this->reflection->returnsReference();
    }

    public function setAccessible(bool $accessible): void {}

    private function loadNative(): void
    {
        if (!$this->nativeLoaded) {
            /** @psalm-suppress ArgumentTypeCoercion */
            parent::__construct($this->reflection->id->class->name, $this->name);
            $this->nativeLoaded = true;
        }
    }
}
