<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\ChangeDetector\InMemoryChangeDetector;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\NativeAdapter\ClassAdapter;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Type\Type;

/**
 * @api
 * @template-covariant TObject of object
 * @template-covariant TId of NamedClassId<class-string<TObject>>|AnonymousClassId<?class-string<TObject>>
 */
final class ClassReflection
{
    /**
     * This internal property is public for testing purposes.
     * It will likely be available as part of the API in the near future.
     *
     * @internal
     * @psalm-internal Typhoon
     */
    public readonly TypedMap $data;

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

    /**
     * @param TId $id
     */
    public function __construct(
        public readonly NamedClassId|AnonymousClassId $id,
        TypedMap $data,
        private readonly TyphoonReflector $reflector,
    ) {
        $this->data = $data;
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
    public function enumCases(): NameMap
    {
        return $this->constants()->filter(static fn(ClassConstantReflection $reflection): bool => $reflection->isEnumCase());
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

    public function isBackedEnum(): bool
    {
        return $this->data[Data::EnumBackingType] !== null;
    }

    public function enumBackingType(): ?Type
    {
        return $this->data[Data::EnumBackingType];
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

    /**
     * Unlike {@see \ReflectionClass::getNamespaceName()}, this method returns the actual namespace
     * an anonymous class is defined in.
     */
    public function namespace(): string
    {
        return $this->data[Data::Namespace];
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
        $name = $this->id->name;

        if ($name === null) {
            return $this->id->toString();
        }

        $lastSlashPosition = strrpos($name, '\\');

        if ($lastSlashPosition === false) {
            return $name;
        }

        $shortName = substr($name, $lastSlashPosition + 1);
        \assert($shortName !== '', 'A valid class name cannot end with a backslash');

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

    /**
     * @return ?positive-int
     */
    public function startLine(): ?int
    {
        return $this->data[Data::StartLine];
    }

    /**
     * @return ?positive-int
     */
    public function endLine(): ?int
    {
        return $this->data[Data::EndLine];
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
        if (!$this->isInstantiable()) {
            throw new \LogicException(sprintf('Class "%s" is not instantiable', $this->id->toString()));
        }

        $name = $this->id->name ?? throw new \LogicException(sprintf(
            "Cannot instantiate anonymous class %s, because it's runtime name is not available",
            $this->id->toString(),
        ));

        return new $name(...$arguments);
    }

    /**
     * @return TObject
     */
    public function newInstanceWithoutConstructor(): object
    {
        if ($this->kind() !== ClassKind::Class_) {
            throw new \LogicException(sprintf('Class "%s" is not instantiable', $this->id->toString()));
        }

        $name = $this->id->name ?? throw new \LogicException(sprintf(
            "Cannot instantiate anonymous class %s, because it's runtime name is not available",
            $this->id->toString(),
        ));

        return (new \ReflectionClass($name))->newInstanceWithoutConstructor();
    }

    /**
     * @var ?\ReflectionClass<TObject>
     */
    private ?\ReflectionClass $native = null;

    /**
     * @return \ReflectionClass<TObject>
     */
    public function toNative(): \ReflectionClass
    {
        return $this->native ??= ClassAdapter::create($this, $this->reflector);
    }
}
