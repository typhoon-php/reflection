<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use PhpParser\Node;
use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\DeclarationId\DeclarationId;
use Typhoon\Reflection\Internal\Expression\Expression;
use Typhoon\Reflection\Internal\TypeContext\TypeContext;
use Typhoon\Type\Type;
use Typhoon\TypedMap\Key;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon
 * @psalm-type MethodName = non-empty-string
 * @psalm-type TraitName = non-empty-string
 */
final class Data
{
    private function __construct() {}

    /**
     * @return Key<bool>
     */
    public static function Abstract(): Key
    {
        return DataEnum::Abstract;
    }

    /**
     * @return Key<bool>
     */
    public static function NativeReadonly(): Key
    {
        return DataEnum::NativeReadonly;
    }

    /**
     * @return Key<bool>
     */
    public static function AnnotatedReadonly(): Key
    {
        return DataEnum::AnnotatedReadonly;
    }

    /**
     * @return Key<bool>
     */
    public static function NativeFinal(): Key
    {
        return DataEnum::NativeFinal;
    }

    /**
     * @return Key<bool>
     */
    public static function AnnotatedFinal(): Key
    {
        return DataEnum::AnnotatedFinal;
    }

    /**
     * @return Key<bool>
     */
    public static function Promoted(): Key
    {
        return DataEnum::Promoted;
    }

    /**
     * @return Key<bool>
     */
    public static function Static(): Key
    {
        return DataEnum::Static;
    }

    /**
     * @return Key<bool>
     */
    public static function ByReference(): Key
    {
        return DataEnum::ByReference;
    }

    /**
     * @return Key<bool>
     */
    public static function Generator(): Key
    {
        return DataEnum::Generator;
    }

    /**
     * @return Key<bool>
     */
    public static function WrittenInC(): Key
    {
        return DataEnum::WrittenInC;
    }

    /**
     * @return Key<bool>
     */
    public static function Variadic(): Key
    {
        return DataEnum::Variadic;
    }

    /**
     * @return Key<Node>
     */
    public static function Node(): Key
    {
        return DataEnum::Node;
    }

    /**
     * @return Key<ClassKind>
     */
    public static function ClassKind(): Key
    {
        return DataEnum::ClassKind;
    }

    /**
     * @return Key<?positive-int>
     */
    public static function StartLine(): Key
    {
        return DataEnum::StartLine;
    }

    /**
     * @return Key<?positive-int>
     */
    public static function EndLine(): Key
    {
        return DataEnum::EndLine;
    }

    /**
     * @return Key<list<TypedMap>>
     */
    public static function Attributes(): Key
    {
        return DataEnum::Attributes;
    }

    /**
     * @return Key<array<non-empty-string, TypedMap>>
     */
    public static function ClassConstants(): Key
    {
        return DataEnum::ClassConstants;
    }

    /**
     * @return Key<array<non-empty-string, TypedMap>>
     */
    public static function Properties(): Key
    {
        return DataEnum::Properties;
    }

    /**
     * @return Key<array<non-empty-string, TypedMap>>
     */
    public static function Methods(): Key
    {
        return DataEnum::Methods;
    }

    /**
     * @return Key<array<non-empty-string, TypedMap>>
     */
    public static function Parameters(): Key
    {
        return DataEnum::Parameters;
    }

    /**
     * @return Key<?non-empty-string>
     */
    public static function File(): Key
    {
        return DataEnum::File;
    }

    /**
     * @return Key<?non-empty-string>
     */
    public static function Extension(): Key
    {
        return DataEnum::Extension;
    }

    /**
     * @return Key<?non-empty-string>
     */
    public static function PhpDoc(): Key
    {
        return DataEnum::PhpDoc;
    }

    /**
     * @return Key<?Visibility>
     */
    public static function Visibility(): Key
    {
        return DataEnum::Visibility;
    }

    /**
     * @return Key<?Expression>
     */
    public static function DefaultValueExpression(): Key
    {
        return DataEnum::DefaultValueExpression;
    }

    /**
     * @return Key<Expression>
     */
    public static function ValueExpression(): Key
    {
        return DataEnum::ValueExpression;
    }

    /**
     * @return Key<Expression>
     */
    public static function BackingValueExpression(): Key
    {
        return DataEnum::BackingValueExpression;
    }

    /**
     * @return Key<?Type>
     */
    public static function NativeType(): Key
    {
        return DataEnum::NativeType;
    }

    /**
     * @return Key<Type>
     */
    public static function TentativeType(): Key
    {
        return DataEnum::TentativeType;
    }

    /**
     * @return Key<Type>
     */
    public static function AnnotatedType(): Key
    {
        return DataEnum::AnnotatedType;
    }

    /**
     * @return Key<Type>
     */
    public static function ResolvedType(): Key
    {
        return DataEnum::ResolvedType;
    }

    /**
     * @return Key<TypeContext>
     */
    public static function TypeContext(): Key
    {
        return DataEnum::TypeContext;
    }

    /**
     * @return Key<?InheritedName>
     */
    public static function UnresolvedParent(): Key
    {
        return DataEnum::UnresolvedParent;
    }

    /**
     * @return Key<list<InheritedName>>
     */
    public static function UnresolvedInterfaces(): Key
    {
        return DataEnum::UnresolvedInterfaces;
    }

    /**
     * @return Key<list<UsedName>>
     */
    public static function UnresolvedTraits(): Key
    {
        return DataEnum::UnresolvedTraits;
    }

    /**
     * @return Key<non-empty-string>
     */
    public static function AttributeClass(): Key
    {
        return DataEnum::AttributeClass;
    }

    /**
     * @return Key<non-negative-int>
     */
    public static function Index(): Key
    {
        return DataEnum::Index;
    }

    /**
     * @return Key<array<Expression>>
     */
    public static function ArgumentExpressions(): Key
    {
        return DataEnum::ArgumentExpressions;
    }

    /**
     * @return Key<bool>
     */
    public static function Repeated(): Key
    {
        return DataEnum::Repeated;
    }

    /**
     * @return Key<bool>
     */
    public static function EnumCase(): Key
    {
        return DataEnum::EnumCase;
    }

    /**
     * @return Key<list<TraitMethodAlias>>
     */
    public static function TraitMethodAliases(): Key
    {
        return DataEnum::TraitMethodAliases;
    }

    /**
     * @return Key<array<MethodName, TraitName>>
     */
    public static function TraitMethodPrecedence(): Key
    {
        return DataEnum::TraitMethodPrecedence;
    }

    /**
     * @return Key<array<class-string, list<Type>>>
     */
    public static function ResolvedInterfaces(): Key
    {
        return DataEnum::ResolvedInterfaces;
    }

    /**
     * @return Key<array<class-string, list<Type>>>
     */
    public static function ResolvedParents(): Key
    {
        return DataEnum::ResolvedParents;
    }

    /**
     * @return Key<DeclarationId>
     */
    public static function DeclarationId(): Key
    {
        return DataEnum::DeclarationId;
    }

    /**
     * @return Key<list<ChangeDetector>>
     */
    public static function UnresolvedChangeDetectors(): Key
    {
        return DataEnum::UnresolvedChangeDetectors;
    }

    /**
     * @return Key<ChangeDetector>
     */
    public static function ResolvedChangeDetector(): Key
    {
        return DataEnum::ResolvedChangeDetector;
    }
}
