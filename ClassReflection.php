<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\Reflection\Internal\ClassKind;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\NativeAdapter\ClassAdapter;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\anyClassId;
use function Typhoon\DeclarationId\classConstantId;
use function Typhoon\DeclarationId\methodId;
use function Typhoon\DeclarationId\propertyId;

/**
 * @api
 * @readonly
 * @extends Reflection<ClassId|AnonymousClassId>
 * @template-covariant TObject of object
 */
final class ClassReflection extends Reflection
{
    /**
     * @var class-string<TObject>
     */
    public readonly string $name;

    /**
     * @var ?array<non-empty-string, ClassConstantReflection>
     */
    private ?array $constants = null;

    /**
     * @var ?array<non-empty-string, PropertyReflection>
     */
    private ?array $properties = null;

    /**
     * @var ?array<non-empty-string, MethodReflection>
     */
    private ?array $methods = null;

    public function __construct(ClassId|AnonymousClassId $id, TypedMap $data, Reflector $reflector)
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->name = $id->name;

        parent::__construct($id, $data, $reflector);
    }

    public function isInstanceOf(string|ClassId|AnonymousClassId $class): bool
    {
        if (\is_string($class)) {
            $class = anyClassId($class);
        }

        return $this->id->equals($class)
            || \array_key_exists($class->name, $this->data[Data::ResolvedParents])
            || \array_key_exists($class->name, $this->data[Data::ResolvedInterfaces]);
    }

    public function isAbstract(): bool
    {
        if ($this->isAbstractClass()) {
            return true;
        }

        if ($this->isInterface() || $this->isTrait()) {
            foreach ($this->data[Data::Methods] as $method) {
                if ($method[Data::Abstract]) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

    public function isAbstractClass(): bool
    {
        return $this->data[Data::Abstract];
    }

    public function isAnonymous(): bool
    {
        return $this->id instanceof AnonymousClassId;
    }

    public function isCloneable(): bool
    {
        return !$this->isAbstract()
            && $this->data[Data::ClassKind] === ClassKind::Class_
            && ($this->method('__clone')?->isPublic() ?? true);
    }

    public function isTrait(): bool
    {
        return $this->data[Data::ClassKind] === ClassKind::Trait;
    }

    public function isEnum(): bool
    {
        return $this->data[Data::ClassKind] === ClassKind::Enum;
    }

    public function isFinal(Kind $kind = Kind::Resolved): bool
    {
        return match ($kind) {
            Kind::Native => $this->data[Data::NativeFinal],
            Kind::Annotated => $this->data[Data::AnnotatedFinal],
            Kind::Resolved => $this->data[Data::NativeFinal] || $this->data[Data::AnnotatedFinal],
        };
    }

    public function isInstantiable(): bool
    {
        return !$this->isAbstract()
            && $this->data[Data::ClassKind] === ClassKind::Class_
            && ($this->method('__construct')?->isPublic() ?? true);
    }

    public function isInterface(): bool
    {
        return $this->data[Data::ClassKind] === ClassKind::Interface;
    }

    public function isReadonly(Kind $kind = Kind::Resolved): bool
    {
        return match ($kind) {
            Kind::Native => $this->data[Data::NativeReadonly],
            Kind::Annotated => $this->data[Data::AnnotatedReadonly],
            Kind::Resolved => $this->data[Data::NativeReadonly] || $this->data[Data::AnnotatedReadonly],
        };
    }

    public function namespace(): string
    {
        $lastSlashPosition = strrpos($this->name, '\\');

        if ($lastSlashPosition === false) {
            return '';
        }

        return substr($this->name, 0, $lastSlashPosition);
    }

    public function parent(): ?self
    {
        $parentName = $this->parentName();

        if ($parentName === null) {
            return null;
        }

        return $this->reflector->reflect(anyClassId($parentName));
    }

    /**
     * @return ?non-empty-string
     */
    public function parentName(): ?string
    {
        return array_key_first($this->data[Data::ResolvedParents]);
    }

    /**
     * @return non-empty-string
     */
    public function shortName(): string
    {
        $lastSlashPosition = strrpos($this->name, '\\');

        if ($lastSlashPosition === false) {
            return $this->name;
        }

        $shortName = substr($this->name, $lastSlashPosition + 1);
        \assert($shortName !== '');

        return $shortName;
    }

    public function constant(string $name): ?ClassConstantReflection
    {
        return $this->constants()[$name] ?? null;
    }

    /**
     * @return array<non-empty-string, ClassConstantReflection>
     */
    public function constants(): array
    {
        if ($this->constants !== null) {
            return $this->constants;
        }

        $this->constants = [];

        foreach ($this->data[Data::ClassConstants] as $name => $data) {
            $this->constants[$name] = new ClassConstantReflection(
                id: classConstantId($this->id, $name),
                data: $data,
                reflector: $this->reflector,
            );
        }

        return $this->constants;
    }

    public function property(string $name): ?PropertyReflection
    {
        return $this->properties()[$name] ?? null;
    }

    /**
     * @return array<non-empty-string, PropertyReflection>
     */
    public function properties(): array
    {
        if ($this->properties !== null) {
            return $this->properties;
        }

        $this->properties = [];

        foreach ($this->data[Data::Properties] as $name => $data) {
            $this->properties[$name] = new PropertyReflection(
                id: propertyId($this->id, $name),
                data: $data,
                reflector: $this->reflector,
            );
        }

        return $this->properties;
    }

    public function method(string $name): ?MethodReflection
    {
        return $this->methods()[$name] ?? null;
    }

    /**
     * @return array<non-empty-string, MethodReflection>
     */
    public function methods(): array
    {
        if ($this->methods !== null) {
            return $this->methods;
        }

        $this->methods = [];

        foreach ($this->data[Data::Methods] as $name => $data) {
            $this->methods[$name] = new MethodReflection(
                id: methodId($this->id, $name),
                data: $data,
                reflector: $this->reflector,
            );
        }

        return $this->methods;
    }

    /**
     * @return ?non-empty-string
     */
    public function extension(): ?string
    {
        return $this->data[Data::PhpExtension];
    }

    /**
     * @return ?non-empty-string
     */
    public function file(): ?string
    {
        return $this->data[Data::File];
    }

    public function isWrittenInC(): bool
    {
        return $this->data[Data::WrittenInC];
    }

    /**
     * @psalm-suppress MixedMethodCall
     * @return TObject
     */
    public function newInstance(mixed ...$arguments): object
    {
        return new $this->name(...$arguments);
    }

    /**
     * @return TObject
     */
    public function newInstanceWithoutConstructor(): object
    {
        return (new \ReflectionClass($this->name))->newInstanceWithoutConstructor();
    }

    public function toNative(): \ReflectionClass
    {
        return new ClassAdapter($this, $this->reflector);
    }
}
