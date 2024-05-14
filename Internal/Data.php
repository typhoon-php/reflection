<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

/**
 * @internal
 * @psalm-internal Typhoon
 */
enum Data
{
    public const Node = Data\NodeKey::Key;
    public const ArgumentExpressions = Data\ArgumentExpressions::Key;
    public const AttributeClass = Data\AttributeClass::Key;
    public const AttributeRepeated = Data\AttributeRepeated::Key;
    public const Attributes = Data\Attributes::Key;
    public const ClassConstants = Data\ClassConstants::Key;
    public const ClassKind = Data\ClassKindKey::Key;
    public const DeclarationId = Data\DeclarationIdKey::Key;
    public const DefaultValueExpression = Data\DefaultValueExpression::Key;
    public const StartLine = Data\StartLine::Key;
    public const EndLine = Data\EndLine::Key;
    public const EnumCase = Data\IsEnumCase::Key;
    public const EnumBackingValueExpression = Data\EnumBackingValueExpression::Key;
    public const EnumScalarType = Data\EnumScalarType::Key;
    public const File = Data\File::Key;
    public const Index = Data\ParameterIndex::Key;
    public const Abstract = Data\IsAbstract::Key;
    public const AnnotatedFinal = Data\IsAnnotatedFinal::Key;
    public const AnnotatedReadonly = Data\IsAnnotatedReadonly::Key;
    public const ByReference = Data\IsByReference::Key;
    public const Generator = Data\IsGenerator::Key;
    public const NativeFinal = Data\IsNativeFinal::Key;
    public const NativeReadonly = Data\IsNativeReadonly::Key;
    public const Promoted = Data\IsPromoted::Key;
    public const Static = Data\IsStatic::Key;
    public const Variadic = Data\IsVariadic::Key;
    public const WrittenInC = Data\IsWrittenInC::Key;
    public const Methods = Data\Methods::Key;
    public const Parameters = Data\Parameters::Key;
    public const PhpDoc = Data\PhpDoc::Key;
    public const PhpExtension = Data\PhpExtension::Key;
    public const Templates = Data\Templates::Key;
    public const Properties = Data\Properties::Key;
    public const ResolvedChangeDetector = Data\ResolvedChangeDetector::Key;
    public const ResolvedInterfaces = Data\ResolvedInterfaces::Key;
    public const ResolvedParents = Data\ResolvedParents::Key;
    public const ThrowsType = Data\ThrowsType::Key;
    public const UsedMethodAliases = Data\UsedMethodAliases::Key;
    public const UsedMethodPrecedence = Data\UsedMethodPrecedence::Key;
    public const TypeContext = Data\TypeContextKey::Key;
    public const UnresolvedChangeDetectors = Data\UnresolvedChangeDetectors::Key;
    public const UnresolvedInterfaces = Data\UnresolvedInterfaces::Key;
    public const UnresolvedParent = Data\UnresolvedParent::Key;
    public const UnresolvedUses = Data\UnresolvedUses::Key;
    public const ValueExpression = Data\ValueExpression::Key;
    public const Visibility = Data\VisibilityKey::Key;
    public const Type = Data\Type::Key;
    public const Variance = Data\VarianceKey::Key;
    public const Constraint = Data\Constraint::Key;
    public const UsePhpDocs = Data\UsePhpDocs::Key;
}
