<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\ChangeDetector\InMemoryChangeDetector;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\DeclarationId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\ClassKind;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\NativeAdapter\ClassAdapter;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\classId;
use function Typhoon\DeclarationId\namedClassId;

/**
 * @api
 * @readonly
 * @extends Reflection<ClassId>
 * @template-covariant TObject of object
 */
final class ClassReflection extends Reflection
{
    /**
     * @var class-string<TObject>
     */
    public readonly string $name;

    /**
     * @var AliasReflection[]
     * @psalm-var NameMap<AliasReflection>
     * @phpstan-var NameMap<AliasReflection>
     */
    public readonly NameMap $aliases;

    /**
     * @var TemplateReflection[]
     * @psalm-var NameMap<TemplateReflection>
     * @phpstan-var NameMap<TemplateReflection>
     */
    public readonly NameMap $templates;

    /**
     * @var ClassConstantReflection[]
     * @psalm-var NameMap<ClassConstantReflection>
     * @phpstan-var NameMap<ClassConstantReflection>
     */
    public readonly NameMap $constants;

    /**
     * @var PropertyReflection[]
     * @psalm-var NameMap<PropertyReflection>
     * @phpstan-var NameMap<PropertyReflection>
     */
    public readonly NameMap $properties;

    /**
     * @var MethodReflection[]
     * @psalm-var NameMap<MethodReflection>
     * @phpstan-var NameMap<MethodReflection>
     */
    public readonly NameMap $methods;

    /**
     * @var AttributeReflection[]
     * @psalm-var ListOf<AttributeReflection>
     * @phpstan-var ListOf<AttributeReflection>
     */
    public readonly ListOf $attributes;

    public function __construct(
        ClassId $id,
        TypedMap $data,
        private readonly Reflector $reflector,
    ) {
        /** @var class-string<TObject> */
        $this->name = $id->name;
        $this->aliases = (new NameMap($data[Data::Aliases]))->map(
            static function (TypedMap $data, string $name) use ($id): AliasReflection {
                \assert($id instanceof NamedClassId);

                return new AliasReflection(DeclarationId::alias($id, $name), $data);
            },
        );
        $this->templates = (new NameMap($data[Data::Templates]))->map(
            static fn(TypedMap $data, string $name): TemplateReflection => new TemplateReflection(DeclarationId::template($id, $name), $data),
        );
        $this->constants = (new NameMap($data[Data::ClassConstants]))->map(
            static fn(TypedMap $data, string $name): ClassConstantReflection => new ClassConstantReflection(DeclarationId::classConstant($id, $name), $data, $reflector),
        );
        $this->properties = (new NameMap($data[Data::Properties]))->map(
            static fn(TypedMap $data, string $name): PropertyReflection => new PropertyReflection(DeclarationId::property($id, $name), $data, $reflector),
        );
        $this->methods = (new NameMap($data[Data::Methods]))->map(
            static fn(TypedMap $data, string $name): MethodReflection => new MethodReflection(DeclarationId::method($id, $name), $data, $reflector),
        );
        $this->attributes = (new ListOf($data[Data::Attributes]))->map(
            static fn(TypedMap $data, int $index): AttributeReflection => new AttributeReflection($id, $index, $data, $reflector),
        );

        parent::__construct($id, $data);
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

    public function isInstanceOf(string|ClassId $class): bool
    {
        if (\is_string($class)) {
            $class = classId($class);
        }

        return $this->id->equals($class)
            || \array_key_exists($class->name, $this->data[Data::Parents])
            || \array_key_exists($class->name, $this->data[Data::Interfaces]);
    }

    public function isAbstract(): bool
    {
        if ($this->isAbstractClass()) {
            return true;
        }

        if ($this->isInterface() || $this->isTrait()) {
            foreach ($this->methods as $method) {
                if ($method->isAbstract()) {
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
            && (!isset($this->methods['__clone']) || $this->methods['__clone']->isPublic());
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
            && (!isset($this->methods['__construct']) || $this->methods['__construct']->isPublic());
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

        return $this->reflector->reflect(namedClassId($parentName));
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
