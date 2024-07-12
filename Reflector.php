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
     *     $id is NamedClassId ? ClassReflection :
     *     $id is AnonymousClassId ? ClassReflection :
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

    /**
     * @param non-empty-string $class
     */
    final public function classExists(string $class): bool
    {
        try {
            $this->reflectClass($class);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @template T of object
     * @param non-empty-string|class-string<T>|T $nameOrObject
     * @return ClassReflection<T>
     */
    final public function reflectClass(string|object $nameOrObject): ClassReflection
    {
        /** @var ClassReflection<T> */
        return $this->reflect(Id::class($nameOrObject));
    }
}
