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
 * @template-covariant TObject of object
 * @template-covariant TId of NamedClassId|AnonymousClassId
 * @extends Reflection<TId>
 */
abstract class ClassLikeReflection extends Reflection
{
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
        NamedClassId|AnonymousClassId $id,
        TypedMap $data,
        protected readonly Reflector $reflector,
    ) {
        parent::__construct($id, $data);
    }

    /**
     * @return AliasReflection[]
     * @psalm-return NameMap<AliasReflection>
     * @phpstan-return NameMap<AliasReflection>
     */
    final public function aliases(): NameMap
    {
        return $this->aliases ??= (new NameMap($this->data[Data::Aliases]))->map(
            fn(TypedMap $data, string $name): AliasReflection => new AliasReflection(Id::alias($this->id, $name), $data),
        );
    }

    /**
     * @return TemplateReflection[]
     * @psalm-return NameMap<TemplateReflection>
     * @phpstan-return NameMap<TemplateReflection>
     */
    final public function templates(): NameMap
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
    final public function attributes(): ListOf
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
    final public function constants(): NameMap
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
    final public function properties(): NameMap
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
    final public function methods(): NameMap
    {
        return $this->methods ??= (new NameMap($this->data[Data::Methods]))->map(
            fn(TypedMap $data, string $name): MethodReflection => new MethodReflection(Id::method($this->id, $name), $data, $this->reflector),
        );
    }

    /**
     * @return ?non-empty-string
     */
    final public function phpDoc(): ?string
    {
        return $this->data[Data::PhpDoc];
    }

    final public function changeDetector(): ChangeDetector
    {
        return $this->data[Data::ChangeDetector] ?? new InMemoryChangeDetector();
    }

    /**
     * @param non-empty-string|NamedClassId|AnonymousClassId $class
     */
    final public function isInstanceOf(string|NamedClassId|AnonymousClassId $class): bool
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
     * @todo anonymous namespace support
     */
    final public function namespace(): string
    {
        $name = $this->id->name;

        if ($name === null) {
            return '';
        }

        $lastSlashPosition = strrpos($name, '\\');

        if ($lastSlashPosition === false) {
            return '';
        }

        return substr($name, 0, $lastSlashPosition);
    }

    /**
     * @return non-empty-string
     */
    final public function shortName(): string
    {
        // TODO
        $name = $this->id->name;

        if ($name === null) {
            return $this->id->toString();
        }

        $lastSlashPosition = strrpos($name, '\\');

        if ($lastSlashPosition === false) {
            return $name;
        }

        $shortName = substr($name, $lastSlashPosition + 1);
        \assert($shortName !== '');

        return $shortName;
    }

    /**
     * @return ?non-empty-string
     */
    final public function extension(): ?string
    {
        return $this->data[Data::PhpExtension];
    }

    /**
     * @return ?non-empty-string
     */
    final public function file(): ?string
    {
        return $this->data[Data::File];
    }

    final public function isInternallyDefined(): bool
    {
        return $this->data[Data::InternallyDefined];
    }

    /**
     * @return \ReflectionClass<TObject>
     */
    final public function toNative(): \ReflectionClass
    {
        return new ClassAdapter($this, $this->reflector);
    }
}
