<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 * @readonly
 * @template-covariant TObject of object
 * @extends ClassLikeReflection<TObject, NamedClassId>
 */
final class ClassReflection extends ClassLikeReflection
{
    /**
     * @var class-string<TObject>
     */
    public readonly string $name;

    public function __construct(NamedClassId $id, TypedMap $data, Reflector $reflector)
    {
        /** @var class-string<TObject> */
        $this->name = $id->name;
        parent::__construct($id, $data, $reflector);
    }

    public function isFinal(Kind $kind = Kind::Resolved): bool
    {
        return match ($kind) {
            Kind::Native => $this->data[Data::NativeFinal],
            Kind::Annotated => $this->data[Data::AnnotatedFinal],
            Kind::Resolved => $this->data[Data::NativeFinal] || $this->data[Data::AnnotatedFinal],
        };
    }

    public function isAbstract(): bool
    {
        return $this->data[Data::Abstract];
    }

    public function isReadonly(Kind $kind = Kind::Resolved): bool
    {
        return match ($kind) {
            Kind::Native => $this->data[Data::NativeReadonly],
            Kind::Annotated => $this->data[Data::AnnotatedReadonly],
            Kind::Resolved => $this->data[Data::NativeReadonly] || $this->data[Data::AnnotatedReadonly],
        };
    }

    public function isIterable(): bool
    {
        return !$this->isAbstract() && $this->isInstanceOf(\Traversable::class);
    }

    public function isCloneable(): bool
    {
        if ($this->isAbstract()) {
            return false;
        }

        return !isset($this->methods()['__clone']) || $this->methods()['__clone']->isPublic();
    }

    public function isInstantiable(): bool
    {
        if ($this->isAbstract()) {
            return false;
        }

        return !isset($this->methods()['__construct']) || $this->methods()['__construct']->isPublic();
    }

    public function parent(): ?self
    {
        $parentName = $this->parentName();

        if ($parentName === null) {
            return null;
        }

        $parent = $this->reflector->reflect(Id::namedClass($parentName));
        \assert($parent instanceof self);

        return $parent;
    }

    /**
     * @return ?class-string
     */
    public function parentName(): ?string
    {
        return array_key_first($this->data[Data::Parents]);
    }

    /**
     * @psalm-suppress MixedMethodCall
     * @return TObject
     */
    public function newInstance(mixed ...$arguments): object
    {
        \assert(!$this->isAbstract());

        return new $this->name(...$arguments);
    }

    /**
     * @return TObject
     */
    public function newInstanceWithoutConstructor(): object
    {
        \assert(!$this->isAbstract());

        return (new \ReflectionClass($this->name))->newInstanceWithoutConstructor();
    }
}
