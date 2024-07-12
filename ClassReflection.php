<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\ChangeDetector\InMemoryChangeDetector;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\NativeAdapter\ClassAdapter;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @api
 * @readonly
 * @extends Reflection<NamedClassId|AnonymousClassId>
 * @template-covariant TObject of object
 * @template-covariant TClass of ?class-string<TObject>
 */
final class ClassReflection extends Reflection
{
    /**
     * This property is nullable, because anonymous class names are no longer available in a process different from the
     * one they were loaded in. Also {@see Reflector::reflect()} and {@see TyphoonReflector::reflectAnonymousClass()}
     * allow to reflect anonymous classes by file and line only.
     *
     * @var TClass
     */
    public readonly ?string $name;

    /**
     * @var ?NameMap<AliasReflection>
     */
    private ?NameMap $aliases = null;

    /**
     * @var ?NameMap<TemplateReflection>
     */
    private ?NameMap $templates = null;

    /**
     * @var ?ListOf<AttributeReflection>
     */
    private ?ListOf $attributes = null;

    /**
     * @var ?NameMap<ClassConstantReflection>
     */
    private ?NameMap $constants = null;

    /**
     * @var ?NameMap<PropertyReflection>
     */
    private ?NameMap $properties = null;

    /**
     * @var ?NameMap<MethodReflection>
     */
    private ?NameMap $methods = null;

    public function __construct(
        NamedClassId|AnonymousClassId $id,
        TypedMap $data,
        private readonly Reflector $reflector,
    ) {
        /** @var TClass */
        $this->name = $id->name;
        parent::__construct($id, $data);
    }

    public function kind(): ClassKind
    {
        return $this->data[Data::ClassKind];
    }

    /**
     * @return AliasReflection[]
     * @psalm-return NameMap<AliasReflection>
     * @phpstan-return NameMap<AliasReflection>
     */
    public function aliases(): NameMap
    {
        return $this->aliases ??= (new NameMap($this->data[Data::Aliases]))->map(
            function (TypedMap $data, string $name): AliasReflection {
                \assert($this->id instanceof NamedClassId);

                return new AliasReflection(Id::alias($this->id, $name), $data);
            },
        );
    }

    /**
     * @return TemplateReflection[]
     * @psalm-return NameMap<TemplateReflection>
     * @phpstan-return NameMap<TemplateReflection>
     */
    public function templates(): NameMap
    {
        return $this->templates ??= (new NameMap($this->data[Data::Templates]))->map(
            fn(TypedMap $data, string $name): TemplateReflection => new TemplateReflection(Id::template($this->id, $name), $data),
        );
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
     * @return ClassConstantReflection[]
     * @psalm-return NameMap<ClassConstantReflection>
     * @phpstan-return NameMap<ClassConstantReflection>
     */
    public function constants(): NameMap
    {
        return $this->constants ??= (new NameMap($this->data[Data::ClassConstants]))->map(
            fn(TypedMap $data, string $name): ClassConstantReflection => new ClassConstantReflection(Id::classConstant($this->id, $name), $data, $this->reflector),
        );
    }

    /**
     * @return PropertyReflection[]
     * @psalm-return NameMap<PropertyReflection>
     * @phpstan-return NameMap<PropertyReflection>
     */
    public function properties(): NameMap
    {
        return $this->properties ??= (new NameMap($this->data[Data::Properties]))->map(
            fn(TypedMap $data, string $name): PropertyReflection => new PropertyReflection(Id::property($this->id, $name), $data, $this->reflector),
        );
    }

    /**
     * @return MethodReflection[]
     * @psalm-return NameMap<MethodReflection>
     * @phpstan-return NameMap<MethodReflection>
     */
    public function methods(): NameMap
    {
        return $this->methods ??= (new NameMap($this->data[Data::Methods]))->map(
            fn(TypedMap $data, string $name): MethodReflection => new MethodReflection(Id::method($this->id, $name), $data, $this->reflector),
        );
    }

    /**
     * @return ?non-empty-string
     */
    public function phpDoc(): ?string
    {
        return $this->data[Data::PhpDoc];
    }

    public function changeDetector(): ChangeDetector
    {
        return $this->data[Data::ChangeDetector] ?? new InMemoryChangeDetector();
    }

    /**
     * @param non-empty-string|NamedClassId|AnonymousClassId $class
     */
    public function isInstanceOf(string|NamedClassId|AnonymousClassId $class): bool
    {
        if (\is_string($class)) {
            $class = Id::class($class);
        }

        if ($this->id->equals($class)) {
            return true;
        }

        if ($class instanceof AnonymousClassId) {
            return false;
        }

        return \array_key_exists($class->name, $this->data[Data::Parents])
            || \array_key_exists($class->name, $this->data[Data::Interfaces]);
    }

    /**
     * This method is different from {@see \ReflectionClass::isAbstract()}.
     * It returns true only for explicitly abstract classes:
     *     abstract class AC {} -> true
     *     class C {} -> false
     *     interface I { public function m() {} } -> false
     *     trait T { abstract public function m() {} } -> false.
     */
    public function isAbstract(): bool
    {
        return $this->data[Data::Abstract];
    }

    /**
     * @psalm-assert-if-false !null $this->name
     */
    public function isAnonymous(): bool
    {
        return $this->id instanceof AnonymousClassId;
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
        return $this->data[Data::ClassKind] === ClassKind::Class_
            && !$this->isAbstract()
            && (!isset($this->methods()['__construct']) || $this->methods()['__construct']->isPublic());
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
        if ($this->name === null) {
            // todo
            return '';
        }

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

        return $this->reflector->reflect(Id::namedClass($parentName));
    }

    /**
     * @return ?class-string
     */
    public function parentName(): ?string
    {
        return array_key_first($this->data[Data::Parents]);
    }

    /**
     * @return non-empty-string
     */
    public function shortName(): string
    {
        if ($this->name === null) {
            return $this->id->toString();
        }

        $lastSlashPosition = strrpos($this->name, '\\');

        if ($lastSlashPosition === false) {
            return $this->name;
        }

        $shortName = substr($this->name, $lastSlashPosition + 1);
        \assert($shortName !== '');

        return $shortName;
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

    public function isInternallyDefined(): bool
    {
        return $this->data[Data::InternallyDefined];
    }

    /**
     * @psalm-suppress MixedMethodCall
     * @return TObject
     */
    public function newInstance(mixed ...$arguments): object
    {
        $this->ensureInstantiable();

        return new $this->name(...$arguments);
    }

    /**
     * @return TObject
     */
    public function newInstanceWithoutConstructor(): object
    {
        $this->ensureInstantiable();

        return (new \ReflectionClass($this->name))->newInstanceWithoutConstructor();
    }

    /**
     * @psalm-assert class-string<TObject> $this->name
     */
    private function ensureInstantiable(): void
    {
        if ($this->name === null) {
            throw new \LogicException(sprintf(
                "Cannot instantiate anonymous class %s, because it's runtime name is not available",
                $this->id->toString(),
            ));
        }

        if ($this->isInterface()) {
            throw new \LogicException(sprintf('Cannot instantiate interface %s', $this->name));
        }

        if ($this->isTrait()) {
            throw new \LogicException(sprintf('Cannot instantiate trait %s', $this->name));
        }

        if ($this->isAbstract()) {
            throw new \LogicException(sprintf('Cannot instantiate abstract class %s', $this->name));
        }

        if ($this->isEnum()) {
            throw new \LogicException(sprintf('Cannot instantiate enum %s', $this->name));
        }
    }

    private ?ClassAdapter $native = null;

    public function toNative(): \ReflectionClass
    {
        return $this->native ??= new ClassAdapter($this, $this->reflector);
    }
}
