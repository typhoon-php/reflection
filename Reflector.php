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
    abstract public function reflect(Id $id): ClassReflection|ClassConstantReflection|PropertyReflection|MethodReflection|ParameterReflection|AliasReflection|TemplateReflection;

    /**
     * @param non-empty-string $class
     * @psalm-assert-if-true class-string $class
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
     * @return (
     *     $nameOrObject is T ? ClassReflection<T, class-string<T>> :
     *     $nameOrObject is class-string<T> ? ClassReflection<T, class-string<T>> :
     *     ClassReflection<object, ?class-string>
     *  )
     */
    final public function reflectClass(string|object $nameOrObject): ClassReflection
    {
        /** @var ClassReflection<T> */
        return $this->reflect(Id::class($nameOrObject));
    }

    /**
     * @param non-empty-string $file
     * @param positive-int $line
     * @param ?positive-int $column
     * @return ClassReflection<object, ?class-string>
     */
    final public function reflectAnonymousClass(string $file, int $line, ?int $column = null): ClassReflection
    {
        return $this->reflect(Id::anonymousClass($file, $line, $column));
    }
}
