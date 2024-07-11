<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassConstantId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\DeclarationId\PropertyId;
use Typhoon\DeclarationId\TemplateId;

/**
 * @api
 */
abstract class Reflector
{
    /**
     * @return (
     *     $id is NamedClassId ? ClassReflection|InterfaceReflection|TraitReflection|EnumReflection :
     *     $id is AnonymousClassId ? AnonymousClassReflection :
     *     $id is ClassConstantId ? ClassConstantReflection :
     *     $id is PropertyId ? PropertyReflection :
     *     $id is MethodId ? MethodReflection :
     *     $id is ParameterId ? ParameterReflection :
     *     $id is AliasId ? AliasReflection :
     *     $id is TemplateId ? TemplateReflection :
     *     never
     * )
     */
    abstract public function reflect(Id $id): Reflection;

    abstract public function reflectResource(Resource $resource): static;

    /**
     * @param non-empty-string $class
     */
    final public function classLikeExists(string $class): bool
    {
        try {
            $this->reflectClassLike($class);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @template T of object
     * @param non-empty-string|class-string<T>|T $nameOrObject
     * @return ClassLikeReflection<T, NamedClassId|AnonymousClassId>
     */
    final public function reflectClassLike(string|object $nameOrObject): ClassLikeReflection
    {
        /** @var ClassLikeReflection<T> */
        return $this->reflect(Id::class($nameOrObject));
    }

    /**
     * @template T of object
     * @param non-empty-string|class-string<T>|T $nameOrObject
     * @return ClassReflection<T>
     */
    final public function reflectClass(string|object $nameOrObject): ClassReflection
    {
        $reflection = $this->reflect(Id::class($nameOrObject));

        if (!$reflection instanceof ClassReflection) {
            throw new \RuntimeException('Not a class!');
        }

        /** @var ClassReflection<T> */
        return $reflection;
    }

    /**
     * @template T of object
     * @param non-empty-string|class-string<T> $name
     * @return InterfaceReflection<T>
     */
    final public function reflectInterface(string $name): InterfaceReflection
    {
        $reflection = $this->reflect(Id::class($name));

        if (!$reflection instanceof InterfaceReflection) {
            throw new \RuntimeException('Not an interface!');
        }

        /** @var InterfaceReflection<T> */
        return $reflection;
    }

    /**
     * @template T of object
     * @param non-empty-string|class-string<T> $name
     * @return TraitReflection<T>
     */
    final public function reflectTrait(string $name): TraitReflection
    {
        $reflection = $this->reflect(Id::class($name));

        if (!$reflection instanceof TraitReflection) {
            throw new \RuntimeException('Not a trait!');
        }

        /** @var TraitReflection<T> */
        return $reflection;
    }

    /**
     * @template T of \UnitEnum
     * @param non-empty-string|class-string<T>|T $nameOrEnum
     * @return EnumReflection<T>
     */
    final public function reflectEnum(string|\UnitEnum $nameOrEnum): EnumReflection
    {
        $reflection = $this->reflect(Id::class($nameOrEnum));

        if (!$reflection instanceof EnumReflection) {
            throw new \RuntimeException('Not an enum!');
        }

        /** @var EnumReflection<T> */
        return $reflection;
    }
}
