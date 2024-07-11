<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\Reflection\ClassConstantReflection;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Kind;
use Typhoon\Reflection\Reflector;
use Typhoon\Reflection\TraitReflection;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @property-read non-empty-string $name
 * @property-read class-string $class
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ClassConstantAdapter extends \ReflectionClassConstant
{
    private bool $nativeLoaded = false;

    public function __construct(
        private readonly ClassConstantReflection $reflection,
        private readonly Reflector $reflector,
    ) {
        unset($this->name, $this->class);
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

    public function getDeclaringClass(): \ReflectionClass
    {
        $declaringClassId = $this->reflection->data[Data::DeclaringClassId];

        if ($declaringClassId->equals($this->reflection->id->class)) {
            $declaringClassId = $this->reflection->id->class;
        }

        $declaringClass = $this->reflector->reflect($declaringClassId);

        if ($declaringClass instanceof TraitReflection) {
            return $this->reflection->class()->toNative();
        }

        return $declaringClass->toNative();
    }

    public function getDocComment(): string|false
    {
        return $this->reflection->phpDoc() ?? false;
    }

    public function getModifiers(): int
    {
        return ($this->isPublic() ? self::IS_PUBLIC : 0)
            | ($this->isProtected() ? self::IS_PROTECTED : 0)
            | ($this->isPrivate() ? self::IS_PRIVATE : 0)
            | ($this->isFinal() ? self::IS_FINAL : 0);
    }

    public function getName(): string
    {
        return $this->reflection->name;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, UnusedPsalmSuppress
     */
    public function getType(): ?\ReflectionType
    {
        if ($this->isEnumCase()) {
            return null;
        }

        return $this->reflection->type(Kind::Native)?->accept(new ToNativeTypeConverter());
    }

    public function getValue(): mixed
    {
        return $this->reflection->value();
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, UnusedPsalmSuppress
     */
    public function hasType(): bool
    {
        return !$this->isEnumCase()
            && $this->reflection->type(Kind::Native) !== null;
    }

    public function isEnumCase(): bool
    {
        return $this->reflection->isEnumCase();
    }

    public function isFinal(): bool
    {
        return $this->reflection->isFinal(Kind::Native);
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

    private function loadNative(): void
    {
        if ($this->nativeLoaded) {
            return;
        }

        $class = $this->reflection->id->class->name ?? throw new \LogicException(sprintf(
            "Cannot natively reflect %s, because it's runtime name is not available",
            $this->reflection->id->class->toString(),
        ));

        /** @psalm-suppress ArgumentTypeCoercion */
        parent::__construct($class, $this->name);
        $this->nativeLoaded = true;
    }
}
