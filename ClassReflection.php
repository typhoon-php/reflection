<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\ChangeDetector\InMemoryChangeDetector;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\Reflection\Internal\ClassKind;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\NativeAdapter\ClassAdapter;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\anyClassId;
use function Typhoon\DeclarationId\classId;

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
     * @var AliasReflection[]
     * @psalm-var AliasReflections
     * @phpstan-var AliasReflections
     */
    public readonly AliasReflections $aliases;

    /**
     * @var TemplateReflection[]
     * @psalm-var TemplateReflections
     * @phpstan-var TemplateReflections
     */
    public readonly TemplateReflections $templates;

    /**
     * @var ClassConstantReflection[]
     * @psalm-var ClassConstantReflections
     * @phpstan-var ClassConstantReflections
     */
    public readonly ClassConstantReflections $constants;

    /**
     * @var PropertyReflection[]
     * @psalm-var PropertyReflections
     * @phpstan-var PropertyReflections
     */
    public readonly PropertyReflections $properties;

    /**
     * @var MethodReflection[]
     * @psalm-var MethodReflections
     * @phpstan-var MethodReflections
     */
    public readonly MethodReflections $methods;

    /**
     * @var AttributeReflection[]
     * @psalm-var AttributeReflections
     * @phpstan-var AttributeReflections
     */
    public readonly AttributeReflections $attributes;

    public function __construct(
        ClassId|AnonymousClassId $id,
        TypedMap $data,
        private readonly Reflector $reflector,
    ) {
        /** @var class-string<TObject> */
        $this->name = $id->name;
        $this->aliases = new AliasReflections($id, $data[Data::Aliases]);
        $this->templates = new TemplateReflections($id, $data[Data::Templates]);
        $this->constants = new ClassConstantReflections($id, $data[Data::ClassConstants], $reflector);
        $this->properties = new PropertyReflections($id, $data[Data::Properties], $reflector);
        $this->methods = new MethodReflections($id, $data[Data::Methods], $reflector);
        $this->attributes = new AttributeReflections($id, $data[Data::Attributes], $reflector);

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

    public function isInstanceOf(string|ClassId|AnonymousClassId $class): bool
    {
        if (\is_string($class)) {
            $class = anyClassId($class);
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

        return $this->reflector->reflect(classId($parentName));
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
