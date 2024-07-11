<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\Id;
use Typhoon\Reflection\Internal\Data;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 * @readonly
 * @extends ClassLikeReflection<object, AnonymousClassId>
 */
final class AnonymousClassReflection extends ClassLikeReflection
{
    /**
     * @var ?class-string
     */
    public readonly ?string $name;

    public function __construct(AnonymousClassId $id, TypedMap $data, Reflector $reflector)
    {
        $this->name = $id->name;
        parent::__construct($id, $data, $reflector);
    }

    public function parent(): ?ClassReflection
    {
        $parentName = $this->parentName();

        if ($parentName === null) {
            return null;
        }

        $parent = $this->reflector->reflect(Id::namedClass($parentName));
        \assert($parent instanceof ClassReflection);

        return $parent;
    }

    /**
     * @return ?class-string
     */
    public function parentName(): ?string
    {
        return array_key_first($this->data[Data::Parents]);
    }

    public function isIterable(): bool
    {
        return $this->isInstanceOf(\Traversable::class);
    }

    public function isCloneable(): bool
    {
        return !isset($this->methods()['__clone']) || $this->methods()['__clone']->isPublic();
    }

    public function isInstantiable(): bool
    {
        return !isset($this->methods()['__construct']) || $this->methods()['__construct']->isPublic();
    }

    public function isFinal(Kind $kind = Kind::Resolved): bool
    {
        return match ($kind) {
            Kind::Native => false,
            Kind::Annotated, Kind::Resolved => $this->data[Data::AnnotatedFinal],
        };
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
     * @psalm-suppress MixedMethodCall
     */
    public function newInstance(mixed ...$arguments): object
    {
        if ($this->name === null) {
            throw new \LogicException(sprintf(
                "Cannot create an instance of %s, because it's runtime name is not available",
                $this->id->toString(),
            ));
        }

        return new $this->name(...$arguments);
    }

    public function newInstanceWithoutConstructor(): object
    {
        if ($this->name === null) {
            throw new \LogicException(sprintf(
                "Cannot create an instance of %s, because it's runtime name is not available",
                $this->id->toString(),
            ));
        }

        return (new \ReflectionClass($this->name))->newInstanceWithoutConstructor();
    }
}
