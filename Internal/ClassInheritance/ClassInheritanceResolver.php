<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ClassInheritance;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\ChangeDetector\ChangeDetectors;
use Typhoon\ChangeDetector\IfSerializedChangeDetector;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\InheritedName;
use Typhoon\Reflection\Internal\UsedName;
use Typhoon\Reflection\Reflector;
use Typhoon\Type\Type;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\classConstantId;
use function Typhoon\DeclarationId\classId;
use function Typhoon\DeclarationId\methodId;
use function Typhoon\DeclarationId\propertyId;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 */
final class ClassInheritanceResolver
{
    /**
     * @var array<non-empty-string, BasicInheritanceResolver>
     */
    private array $constants = [];

    /**
     * @var array<non-empty-string, BasicInheritanceResolver>
     */
    private array $properties = [];

    /**
     * @var array<non-empty-string, MethodInheritanceResolver>
     */
    private array $methods = [];

    /**
     * @var array<class-string, list<Type>>
     */
    private array $resolvedOwnInterfaces = [];

    /**
     * @var array<class-string, list<Type>>
     */
    private array $resolvedUpstreamInterfaces = [];

    /**
     * @var array<class-string, list<Type>>
     */
    private array $resolvedParents = [];

    /**
     * @var list<ChangeDetector>
     */
    private array $changeDetectors = [];

    private function __construct(
        private readonly Reflector $reflector,
        private readonly ClassId|AnonymousClassId $id,
        private readonly TypedMap $data,
    ) {}

    public static function resolve(Reflector $reflector, ClassId|AnonymousClassId $id, TypedMap $data): TypedMap
    {
        $resolver = new self($reflector, $id, $data);
        $resolver->own();
        $resolver->used();
        $resolver->inherited();

        return $resolver->doResolve();
    }

    private function own(): void
    {
        foreach ($this->data[Data::ClassConstants()] ?? [] as $name => $constant) {
            $this->constant($name)->setOwn($constant->with(Data::DeclarationId(), classConstantId($this->id, $name)));
        }

        foreach ($this->data[Data::Properties()] ?? [] as $name => $property) {
            $this->property($name)->setOwn($property->with(Data::DeclarationId(), propertyId($this->id, $name)));
        }

        foreach ($this->data[Data::Methods()] ?? [] as $name => $method) {
            $this->method($name)->setOwn($method->with(Data::DeclarationId(), methodId($this->id, $name)));
        }
    }

    private function used(): void
    {
        foreach ($this->data[Data::UnresolvedTraits()] ?? [] as $traitName) {
            $this->oneUsed($traitName);
        }
    }

    private function oneUsed(UsedName $traitName): void
    {
        $trait = $this->reflector->reflect(classId($traitName->name));

        $this->changeDetectors[] = $trait->changeDetector();

        // $typeResolver = $this->typeResolver($trait, $arguments);
        $typeResolver = new TypeProcessor([]);

        foreach ($trait->data[Data::ClassConstants()] as $name => $constant) {
            $this->constant($name)->addUsed($constant, $typeResolver);
        }

        foreach ($trait->data[Data::Properties()] as $name => $property) {
            $this->property($name)->addUsed($property, $typeResolver);
        }

        foreach ($trait->data[Data::Methods()] as $name => $method) {
            $precedence = $this->data[Data::TraitMethodPrecedence()][$name] ?? null;

            if ($precedence !== null && $precedence !== $traitName->name) {
                continue;
            }

            foreach ($this->data[Data::TraitMethodAliases()] ?? [] as $alias) {
                if ($alias->trait !== $traitName->name || $alias->method !== $name) {
                    continue;
                }

                $methodToUse = $method;

                if ($alias->newVisibility !== null) {
                    $methodToUse = $methodToUse->with(Data::Visibility(), $alias->newVisibility);
                }

                $this->method($alias->newName ?? $name)->addUsed($methodToUse, $typeResolver);
            }

            $this->method($name)->addUsed($method, $typeResolver);
        }
    }

    private function inherited(): void
    {
        $parent = $this->data[Data::UnresolvedParent()] ?? null;

        if ($parent !== null) {
            $this->oneInherited($parent);
        }

        foreach ($this->data[Data::UnresolvedInterfaces()] ?? [] as $interface) {
            $this->oneInherited($interface);
        }
    }

    private function oneInherited(InheritedName $className): void
    {
        $class = $this->reflector->reflect(classId($className->name));

        $this->changeDetectors[] = $class->changeDetector();

        $this->resolvedUpstreamInterfaces = [
            ...$this->resolvedUpstreamInterfaces,
            ...($class->data[Data::ResolvedInterfaces()] ?? []),
        ];

        if ($class->isInterface()) {
            $this->resolvedOwnInterfaces[$class->name] ??= $className->arguments;
        } else {
            $this->resolvedParents = [
                $class->name => $className->arguments,
                ...($class->data[Data::ResolvedParents()] ?? []),
            ];
        }

        // $typeResolver = $this->typeResolver($class, $arguments);
        $typeResolver = new TypeProcessor([]);

        foreach ($class->data[Data::ClassConstants()] as $name => $constant) {
            $this->constant($name)->addInherited($constant, $typeResolver);
        }

        foreach ($class->data[Data::Properties()] as $name => $property) {
            $this->property($name)->addInherited($property, $typeResolver);
        }

        foreach ($class->data[Data::Methods()] as $name => $method) {
            $this->method($name)->addInherited($method, $typeResolver);
        }
    }

    private function resolveChangeDetector(): ChangeDetector
    {
        return ChangeDetectors::from([
            ...$this->changeDetectors,
            ...($this->data[Data::UnresolvedChangeDetectors()] ?? []),
        ]) ?? new IfSerializedChangeDetector();
    }

    private function doResolve(): TypedMap
    {
        return $this
            ->data
            ->with(Data::ResolvedChangeDetector(), $this->resolveChangeDetector())
            ->with(Data::ResolvedParents(), $this->resolvedParents)
            ->with(Data::ResolvedInterfaces(), [...$this->resolvedOwnInterfaces, ...$this->resolvedUpstreamInterfaces])
            ->with(Data::ClassConstants(), array_filter(array_map(
                static fn(BasicInheritanceResolver $resolver): ?TypedMap => $resolver->resolve(),
                $this->constants,
            )))
            ->with(Data::Properties(), array_filter(array_map(
                static fn(BasicInheritanceResolver $resolver): ?TypedMap => $resolver->resolve(),
                $this->properties,
            )))
            ->with(Data::Methods(), array_filter(array_map(
                static fn(MethodInheritanceResolver $resolver): ?TypedMap => $resolver->resolve(),
                $this->methods,
            )));
    }

    /**
     * @param non-empty-string $name
     */
    private function constant(string $name): BasicInheritanceResolver
    {
        return $this->constants[$name] ??= new BasicInheritanceResolver();
    }

    /**
     * @param non-empty-string $name
     */
    private function property(string $name): BasicInheritanceResolver
    {
        return $this->properties[$name] ??= new BasicInheritanceResolver();
    }

    /**
     * @param non-empty-string $name
     */
    private function method(string $name): MethodInheritanceResolver
    {
        return $this->methods[$name] ??= new MethodInheritanceResolver();
    }
}
