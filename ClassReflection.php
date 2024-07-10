<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\ChangeDetector\InMemoryChangeDetector;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousClassNameNotAvailable;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\ClassKind;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\NativeAdapter\ClassAdapter;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 * @readonly
 * @extends Reflection<NamedClassId|AnonymousClassId>
 * @template-covariant TObject of object
 */
final class ClassReflection extends Reflection
{
    /**
     * @var ?class-string<TObject>
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
        /** @var ?class-string<TObject> */
        $this->name = $id->name;
        parent::__construct($id, $data);
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
     * @psalm-assert-if-true !null $this->name
     */
    public function isAbstract(): bool
    {
        if ($this->isAbstractClass()) {
            return true;
        }

        if ($this->isInterface() || $this->isTrait()) {
            foreach ($this->methods() as $method) {
                if ($method->isAbstract()) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

    /**
     * @psalm-assert-if-true !null $this->name
     */
    public function isAbstractClass(): bool
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

    public function isCloneable(): bool
    {
        return !$this->isAbstract()
            && $this->data[Data::ClassKind] === ClassKind::Class_
            && (!isset($this->methods()['__clone']) || $this->methods()['__clone']->isPublic());
    }

    /**
     * @psalm-assert-if-true !null $this->name
     */
    public function isTrait(): bool
    {
        return $this->data[Data::ClassKind] === ClassKind::Trait;
    }

    /**
     * @psalm-assert-if-true !null $this->name
     */
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
            && (!isset($this->methods()['__construct']) || $this->methods()['__construct']->isPublic());
    }

    /**
     * @psalm-assert-if-true !null $this->name
     */
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

    /**
     * @psalm-assert-if-true !null $this->name
     */
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
        if ($this->name === null) {
            throw new AnonymousClassNameNotAvailable(sprintf(
                "Cannot create an instance of anonymous class %s, because it's runtime name is not available",
                $this->id->toString(),
            ));
        }

        return new $this->name(...$arguments);
    }

    /**
     * @return TObject
     */
    public function newInstanceWithoutConstructor(): object
    {
        if ($this->name === null) {
            throw new AnonymousClassNameNotAvailable(sprintf(
                "Cannot create an instance of anonymous class %s, because it's runtime name is not available",
                $this->id->toString(),
            ));
        }

        return (new \ReflectionClass($this->name))->newInstanceWithoutConstructor();
    }

    public function toNative(): \ReflectionClass
    {
        return new ClassAdapter($this, $this->reflector);
    }
}
