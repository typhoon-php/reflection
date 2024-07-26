<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Inheritance;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\ClassKind;
use Typhoon\Type\Type;
use Typhoon\Type\Visitor\RelativeClassTypeResolver;
use Typhoon\Type\Visitor\TemplateTypeResolver;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class TypeResolver
{
    /**
     * @param list<Type> $typeArguments
     */
    public static function from(
        NamedClassId|AnonymousClassId $currentId,
        TypedMap $currentData,
        NamedClassId $inheritedId,
        TypedMap $inheritedData,
        array $typeArguments,
    ): self {
        $relativeClassTypeResolver = null;
        $templateTypeResolver = null;

        if ($currentData[Data::ClassKind] !== ClassKind::Trait) {
            $parent = $currentData[Data::UnresolvedParent];
            $relativeClassTypeResolver = new RelativeClassTypeResolver(
                self: $currentId,
                parent: $parent === null ? null : Id::namedClass($parent[0]),
            );
        }

        $templates = $inheritedData[Data::Templates];

        if ($templates !== []) {
            $templateTypeResolver = new TemplateTypeResolver(array_map(
                static fn(int $index, string $name, TypedMap $template): array => [
                    Id::template($inheritedId, $name),
                    $typeArguments[$index] ?? $template[Data::Constraint],
                ],
                range(0, \count($templates) - 1),
                array_keys($templates),
                $templates,
            ));
        }

        return new self($relativeClassTypeResolver, $templateTypeResolver);
    }

    private function __construct(
        private readonly ?RelativeClassTypeResolver $relativeClassTypeResolver,
        private readonly ?TemplateTypeResolver $templateTypeResolver,
    ) {}

    /**
     * @return ($type is null ? null : Type)
     */
    public function resolveNativeType(?Type $type): ?Type
    {
        if ($type === null) {
            return null;
        }

        if ($this->relativeClassTypeResolver !== null) {
            $type = $type->accept($this->relativeClassTypeResolver);
        }

        return $type;
    }

    /**
     * @return ($type is null ? null : Type)
     */
    public function resolveType(?Type $type): ?Type
    {
        if ($type === null) {
            return null;
        }

        if ($this->relativeClassTypeResolver !== null) {
            $type = $type->accept($this->relativeClassTypeResolver);
        }

        if ($this->templateTypeResolver !== null) {
            $type = $type->accept($this->templateTypeResolver);
        }

        return $type;
    }
}
