<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ResolveClassInheritance;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\Internal\ClassKind;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Reflector;
use Typhoon\Reflection\TemplateReflection;
use Typhoon\Type\Type;
use Typhoon\Type\Visitor\SelfParentStaticTypeResolver;
use Typhoon\Type\Visitor\TemplateTypeResolver;
use Typhoon\TypedMap\TypedMap;

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
        private readonly Reflector $reflector,
        private readonly NamedClassId|AnonymousClassId $id,
        private readonly TypedMap $data,
    ) {
        $this->changeDetectors = $data[Data::UnresolvedChangeDetectors];
    }

    public static function resolve(Reflector $reflector, NamedClassId|AnonymousClassId $id, TypedMap $data): TypedMap
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
        $trait = $this->reflector->reflect(Id::namedClass($traitName));

        $this->changeDetectors[] = $trait->changeDetector();

        $typeProcessor = $this->typeProcessor($trait, $arguments);

        foreach ($trait->data[Data::ClassConstants] as $name => $constant) {
            $this->constant($name)->addUsed($constant, $typeProcessor);
        }

        foreach ($trait->data[Data::Properties] as $name => $property) {
            $this->property($name)->addUsed($property, $typeProcessor);
        }

        foreach ($trait->data[Data::Methods] as $name => $method) {
            $precedence = $this->data[Data::TraitMethodPrecedence][$name] ?? null;

            if ($precedence !== null && $precedence !== $traitName) {
                continue;
            }

            foreach ($this->data[Data::TraitMethodAliases] as $alias) {
                if ($alias->trait !== $traitName || $alias->method !== $name) {
                    continue;
                }

                $methodToUse = $method;

                if ($alias->newVisibility !== null) {
                    $methodToUse = $methodToUse->set(Data::Visibility, $alias->newVisibility);
                }

                $this->method($alias->newName ?? $name)->addUsed($methodToUse, $typeProcessor);
            }

            $this->method($name)->addUsed($method, $typeProcessor);
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
        $class = $this->reflector->reflect(Id::namedClass($className));

        $this->changeDetectors[] = $class->changeDetector();

        $this->resolvedUpstreamInterfaces = [
            ...$this->resolvedUpstreamInterfaces,
            ...$class->data[Data::Interfaces],
        ];

        if ($class->isInterface()) {
            $this->resolvedOwnInterfaces[$class->name] ??= $arguments;
        } else {
            /** @psalm-suppress PropertyTypeCoercion */
            $this->resolvedParents = [
                $class->name => $arguments,
                ...$class->data[Data::Parents],
            ];
        }

        $typeProcessor = $this->typeProcessor($class, $arguments);

        foreach ($class->data[Data::ClassConstants] as $name => $constant) {
            $this->constant($name)->addInherited($constant, $typeProcessor);
        }

        foreach ($class->data[Data::Properties] as $name => $property) {
            $this->property($name)->addInherited($property, $typeProcessor);
        }

        foreach ($class->data[Data::Methods] as $name => $method) {
            $this->method($name)->addInherited($method, $typeProcessor);
        }
    }

    /**
     * @param list<Type> $arguments
     */
    private function typeProcessor(ClassReflection $class, array $arguments): TypeProcessor
    {
        $processors = [];

        if (!$class->templates()->isEmpty()) {
            $processors[] = new TemplateTypeResolver($class->templates()->map(
                static fn(TemplateReflection $template): array => [
                    $template->id,
                    $arguments[$template->index] ?? $template->constraint(),
                ],
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
