<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ResolveClassInheritance;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\ChangeDetector\InMemoryChangeDetector;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\ClassKind;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\DataReflector;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Type\Type;
use Typhoon\Type\Visitor\SelfParentStaticTypeResolver;
use Typhoon\Type\Visitor\TemplateTypeResolver;

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
    private array $changeDetectors;

    private function __construct(
        private readonly DataReflector $reflector,
        private readonly NamedClassId|AnonymousClassId $id,
        private readonly TypedMap $data,
    ) {
        $this->changeDetectors = $data[Data::UnresolvedChangeDetectors];
    }

    public static function resolve(DataReflector $reflector, NamedClassId|AnonymousClassId $id, TypedMap $data): TypedMap
    {
        $resolver = new self($reflector, $id, $data);
        $resolver->own();
        $resolver->used();
        $resolver->inherited();

        return $resolver->doResolve();
    }

    private function own(): void
    {
        foreach ($this->data[Data::ClassConstants] as $name => $constant) {
            $this->constant($name)->setOwn($constant->set(Data::DeclaringClassId, $this->id));
        }

        foreach ($this->data[Data::Properties] as $name => $property) {
            $this->property($name)->setOwn($property->set(Data::DeclaringClassId, $this->id));
        }

        foreach ($this->data[Data::Methods] as $name => $method) {
            $this->method($name)->setOwn($method->set(Data::DeclaringClassId, $this->id));
        }
    }

    private function used(): void
    {
        foreach ($this->data[Data::UnresolvedTraits] as $traitName => $arguments) {
            $this->oneUsed($traitName, $arguments);
        }
    }

    /**
     * @param non-empty-string $traitName
     * @param list<Type> $arguments
     */
    private function oneUsed(string $traitName, array $arguments): void
    {
        $traitId = Id::namedClass($traitName);
        $trait = $this->reflector->reflectData($traitId);

        $this->changeDetectors[] = $trait[Data::ChangeDetector] ?? new InMemoryChangeDetector();

        $typeProcessor = $this->typeProcessor($traitId, $trait, $arguments);

        foreach ($trait[Data::ClassConstants] as $constantName => $constant) {
            $this->constant($constantName)->addUsed($constant, $typeProcessor);
        }

        foreach ($trait[Data::Properties] as $propertyName => $property) {
            $this->property($propertyName)->addUsed($property, $typeProcessor);
        }

        foreach ($trait[Data::Methods] as $methodName => $method) {
            $precedence = $this->data[Data::TraitMethodPrecedence][$methodName] ?? null;

            if ($precedence !== null && $precedence !== $traitName) {
                continue;
            }

            foreach ($this->data[Data::TraitMethodAliases] as $alias) {
                if ($alias->trait !== $traitName || $alias->method !== $methodName) {
                    continue;
                }

                $methodToUse = $method;

                if ($alias->newVisibility !== null) {
                    $methodToUse = $methodToUse->set(Data::Visibility, $alias->newVisibility);
                }

                $this->method($alias->newName ?? $methodName)->addUsed($methodToUse, $typeProcessor);
            }

            $this->method($methodName)->addUsed($method, $typeProcessor);
        }
    }

    private function inherited(): void
    {
        $parent = $this->data[Data::UnresolvedParent];

        if ($parent !== null) {
            $this->oneInherited(...$parent);
        }

        foreach ($this->data[Data::UnresolvedInterfaces] as $interface => $arguments) {
            $this->oneInherited($interface, $arguments);
        }
    }

    /**
     * @param non-empty-string $className
     * @param list<Type> $arguments
     */
    private function oneInherited(string $className, array $arguments): void
    {
        $classId = Id::namedClass($className);
        $class = $this->reflector->reflectData($classId);

        $this->changeDetectors[] = $class[Data::ChangeDetector] ?? new InMemoryChangeDetector();

        $this->resolvedUpstreamInterfaces = [
            ...$this->resolvedUpstreamInterfaces,
            ...$class[Data::Interfaces],
        ];

        /** @var class-string $className */
        if ($class[Data::ClassKind] === ClassKind::Interface) {
            $this->resolvedOwnInterfaces[$className] ??= $arguments;
        } else {
            $this->resolvedParents = [$className => $arguments, ...$class[Data::Parents]];
        }

        $typeProcessor = $this->typeProcessor($classId, $class, $arguments);

        foreach ($class[Data::ClassConstants] as $constantName => $constant) {
            $this->constant($constantName)->addInherited($constant, $typeProcessor);
        }

        foreach ($class[Data::Properties] as $propertyName => $property) {
            $this->property($propertyName)->addInherited($property, $typeProcessor);
        }

        foreach ($class[Data::Methods] as $methodName => $method) {
            $this->method($methodName)->addInherited($method, $typeProcessor);
        }
    }

    /**
     * @param list<Type> $arguments
     */
    private function typeProcessor(NamedClassId $id, TypedMap $data, array $arguments): TypeProcessor
    {
        $processors = [];
        $templates = $data[Data::Templates];

        if ($templates !== []) {
            $processors[] = new TemplateTypeResolver(array_map(
                static fn(string $name, TypedMap $template): array => [
                    Id::template($id, $name),
                    $arguments[$template[Data::Index]] ?? $template[Data::Constraint],
                ],
                array_keys($templates),
                $templates,
            ));
        }

        if ($this->data[Data::ClassKind] !== ClassKind::Trait) {
            $parent = $this->data[Data::UnresolvedParent];
            $processors[] = new SelfParentStaticTypeResolver($this->id, $parent === null ? null : Id::namedClass($parent[0]));
        }

        return new TypeProcessor($processors);
    }

    private function doResolve(): TypedMap
    {
        return $this
            ->data
            ->set(Data::UnresolvedChangeDetectors, $this->changeDetectors)
            ->set(Data::Parents, $this->resolvedParents)
            ->set(Data::Interfaces, [...$this->resolvedOwnInterfaces, ...$this->resolvedUpstreamInterfaces])
            ->set(Data::ClassConstants, array_filter(array_map(
                static fn(BasicInheritanceResolver $resolver): ?TypedMap => $resolver->resolve(),
                $this->constants,
            )))
            ->set(Data::Properties, array_filter(array_map(
                static fn(BasicInheritanceResolver $resolver): ?TypedMap => $resolver->resolve(),
                $this->properties,
            )))
            ->set(Data::Methods, array_filter(array_map(
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
