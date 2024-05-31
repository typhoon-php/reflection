<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\Reflection\ClassConstantReflection;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\Exception\ClassDoesNotExist;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Kind;
use Typhoon\Reflection\MethodReflection;
use Typhoon\Reflection\PropertyReflection;
use Typhoon\Reflection\Reflector;
use function Typhoon\DeclarationId\classId;
use function Typhoon\DeclarationId\namedClassId;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template-covariant T of object
 * @extends \ReflectionClass<T>
 * @property-read class-string<T> $name
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ClassAdapter extends \ReflectionClass
{
    public const IS_READONLY = 65536;

    private bool $nativeLoaded = false;

    /**
     * @param ClassReflection<T> $reflection
     */
    public function __construct(
        private readonly ClassReflection $reflection,
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

    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        return AttributeAdapter::fromList($this->reflection->attributes, $name, $flags);
    }

    public function getConstant(string $name): mixed
    {
        return isset($this->reflection->constants[$name])
            ? $this->reflection->constants[$name]->value()
            : false;
    }

    public function getConstants(?int $filter = null): array
    {
        return $this
            ->reflection
            ->constants
            ->filter(static fn(ClassConstantReflection $constant): bool => $filter === null || ($constant->toNative()->getModifiers() & $filter) !== 0)
            ->map(static fn(ClassConstantReflection $constant): mixed => $constant->value())
            ->toArray();
    }

    public function getConstructor(): ?\ReflectionMethod
    {
        return ($this->reflection->methods['__construct'] ?? null)?->toNative();
    }

    public function getDefaultProperties(): array
    {
        return $this
            ->reflection
            ->properties
            ->filter(static fn(PropertyReflection $property): bool => $property->hasDefaultValue())
            ->map(static fn(PropertyReflection $property): mixed => $property->defaultValue())
            ->toArray();
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
        $extension = $this->reflection->extension();

        if ($extension === null) {
            return null;
        }

        return new \ReflectionExtension($extension);
    }

    public function getExtensionName(): string|false
    {
        return $this->reflection->extension() ?? false;
    }

    public function getFileName(): string|false
    {
        return $this->reflection->file() ?? false;
    }

    public function getInterfaceNames(): array
    {
        return array_keys($this->reflection->data[Data::Interfaces]);
    }

    public function getInterfaces(): array
    {
        $interfaces = $this->getInterfaceNames();

        return array_combine($interfaces, array_map(
            fn(string $name): \ReflectionClass => $this->reflector->reflect(classId($name))->toNative(),
            $interfaces,
        ));
    }

    public function getMethod(string $name): \ReflectionMethod
    {
        return $this->reflection->methods[$name]->toNative();
    }

    public function getMethods(?int $filter = null): array
    {
        return $this
            ->reflection
            ->methods
            ->map(static fn(MethodReflection $method): \ReflectionMethod => $method->toNative())
            ->filter(static fn(\ReflectionMethod $method): bool => $filter === null || ($method->getModifiers() & $filter) !== 0)
            ->toList();
    }

    public function getModifiers(): int
    {
        if ($this->isEnum()) {
            return self::IS_FINAL;
        }

        /** @var int-mask-of<\ReflectionClass::IS_*> */
        return ($this->reflection->isAbstractClass() ? self::IS_EXPLICIT_ABSTRACT : 0)
            | ($this->isFinal() ? self::IS_FINAL : 0)
            | ($this->isReadonly() ? self::IS_READONLY : 0);
    }

    public function getName(): string
    {
        return $this->reflection->name;
    }

    public function getNamespaceName(): string
    {
        return $this->reflection->namespace();
    }

    public function getParentClass(): \ReflectionClass|false
    {
        return $this->reflection->parent()?->toNative() ?? false;
    }

    public function getProperties(?int $filter = null): array
    {
        return $this
            ->reflection
            ->properties
            ->map(static fn(PropertyReflection $property): \ReflectionProperty => $property->toNative())
            ->filter(static fn(\ReflectionProperty $property): bool => $filter === null || ($property->getModifiers() & $filter) !== 0)
            ->toList();
    }

    public function getProperty(string $name): \ReflectionProperty
    {
        return $this->reflection->properties[$name]->toNative();
    }

    public function getReflectionConstant(string $name): \ReflectionClassConstant|false
    {
        return ($this->reflection->constants[$name] ?? null)?->toNative() ?? false;
    }

    public function getReflectionConstants(?int $filter = null): array
    {
        return $this
            ->reflection
            ->constants
            ->map(static fn(ClassConstantReflection $constant): \ReflectionClassConstant => $constant->toNative())
            ->filter(static fn(\ReflectionClassConstant $constant): bool => $filter === null || ($constant->getModifiers() & $filter) !== 0)
            ->toList();
    }

    public function getShortName(): string
    {
        return $this->reflection->shortName();
    }

    public function getStartLine(): int|false
    {
        return $this->reflection->startLine() ?? false;
    }

    public function getStaticProperties(): array
    {
        $this->loadNative();

        return parent::getStaticProperties();
    }

    public function getStaticPropertyValue(string $name, mixed $default = null): mixed
    {
        $this->loadNative();

        return parent::getStaticPropertyValue($name, $default);
    }

    public function getTraitAliases(): array
    {
        return $this->reflection->data[NativeTraitInfoKey::Key]->aliases;
    }

    public function getTraitNames(): array
    {
        /** @var list<trait-string> */
        return $this->reflection->data[NativeTraitInfoKey::Key]->names;
    }

    public function getTraits(): array
    {
        $traits = [];

        foreach ($this->getTraitNames() as $name) {
            $traits[$name] = $this->reflector->reflect(namedClassId($name))->toNative();
        }

        return $traits;
    }

    public function hasConstant(string $name): bool
    {
        return isset($this->reflection->constants[$name]);
    }

    public function hasMethod(string $name): bool
    {
        return isset($this->reflection->methods[$name]);
    }

    public function hasProperty(string $name): bool
    {
        return isset($this->reflection->properties[$name]);
    }

    public function implementsInterface(string|\ReflectionClass $interface): bool
    {
        if (\is_string($interface)) {
            try {
                $interfaceId = classId($interface);
            } catch (\AssertionError) {
                throw new \ReflectionException(sprintf('Interface "%s" does not exist', ClassNameNormalizer::normalize($interface)));
            }

            try {
                $interfaceReflection = $this->reflector->reflect($interfaceId)->toNative();
            } catch (ClassDoesNotExist) {
                throw new \ReflectionException(sprintf('Interface "%s" does not exist', ClassNameNormalizer::normalize($interface)));
            }
        } else {
            $interfaceReflection = $interface;
        }

        if (!$interfaceReflection->isInterface()) {
            throw new \ReflectionException(sprintf('%s is not an interface', ClassNameNormalizer::normalize($interfaceReflection->name)));
        }

        return $this->reflection->isInstanceOf($interfaceReflection->name);
    }

    public function inNamespace(): bool
    {
        return str_contains($this->name, '\\');
    }

    public function isAbstract(): bool
    {
        return $this->reflection->isAbstract();
    }

    public function isAnonymous(): bool
    {
        return $this->reflection->isAnonymous();
    }

    public function isCloneable(): bool
    {
        return $this->reflection->isCloneable();
    }

    public function isEnum(): bool
    {
        return $this->reflection->isEnum();
    }

    public function isFinal(): bool
    {
        return $this->reflection->isFinal(Kind::Native);
    }

    public function isInstance(object $object): bool
    {
        if ($this->isInterface()) {
            return isset(class_implements($object)[$this->name]);
        }

        return $object::class === $this->name || isset(class_parents($object)[$this->name]);
    }

    public function isInstantiable(): bool
    {
        return $this->reflection->isInstantiable();
    }

    public function isInterface(): bool
    {
        return $this->reflection->isInterface();
    }

    public function isInternal(): bool
    {
        return $this->reflection->isInternallyDefined();
    }

    public function isIterable(): bool
    {
        return !$this->reflection->isInterface()
            && !$this->reflection->isAbstract()
            && $this->reflection->isInstanceOf(\Traversable::class);
    }

    public function isIterateable(): bool
    {
        return $this->isIterable();
    }

    public function isReadonly(): bool
    {
        return $this->reflection->isReadonly(Kind::Native);
    }

    public function isSubclassOf(string|\ReflectionClass $class): bool
    {
        if (\is_string($class)) {
            if ($class === $this->name) {
                return false;
            }

            try {
                $classId = classId($class);
            } catch (\AssertionError) {
                throw new \ReflectionException(sprintf('Class "%s" does not exist', ClassNameNormalizer::normalize($class)));
            }

            try {
                $this->reflector->reflect($classId);
            } catch (ClassDoesNotExist) {
                throw new \ReflectionException(sprintf('Class "%s" does not exist', ClassNameNormalizer::normalize($class)));
            }

            return $this->reflection->isInstanceOf($class);
        }

        return $class->name !== $this->name && $this->reflection->isInstanceOf($class->name);
    }

    public function isTrait(): bool
    {
        return $this->reflection->isTrait();
    }

    public function isUserDefined(): bool
    {
        return !$this->isInternal();
    }

    public function newInstance(mixed ...$args): object
    {
        return $this->reflection->newInstance(...$args);
    }

    /**
     * @psalm-suppress MethodSignatureMismatch
     */
    public function newInstanceArgs(array $args = []): object
    {
        return $this->reflection->newInstance(...$args);
    }

    public function newInstanceWithoutConstructor(): object
    {
        $this->loadNative();

        return parent::newInstanceWithoutConstructor();
    }

    public function setStaticPropertyValue(string $name, mixed $value): void
    {
        $this->loadNative();

        parent::setStaticPropertyValue($name, $value);
    }

    private function loadNative(): void
    {
        if (!$this->nativeLoaded) {
            parent::__construct($this->name);
            $this->nativeLoaded = true;
        }
    }
}
