<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

/**
 * @internal
 * @psalm-internal Typhoon
 */
enum Data
{
    public const Node = NodeKey::Key;
    public const ArgumentExpressions = ArgumentExpressionsKey::Key;
    public const AttributeClassName = AttributeClassNameKey::Key;
    public const AttributeRepeated = AttributeRepeatedKey::Key;
    public const Attributes = AttributesKey::Key;
    public const ClassConstants = ClassConstantsKey::Key;
    public const ClassKind = ClassKindKey::Key;
    public const DeclaringClassId = DeclaringClassIdKey::Key;
    public const DefaultValueExpression = DefaultValueExpressionKey::Key;
    public const StartLine = StartLineKey::Key;
    public const EndLine = EndLineKey::Key;
    public const EnumCase = IsEnumCaseKey::Key;
    public const EnumBackingValueExpression = EnumBackingValueExpressionKey::Key;
    public const EnumScalarType = EnumScalarTypeKey::Key;
    public const File = FileKey::Key;
    public const Index = ParameterIndexKey::Key;
    public const Abstract = IsAbstractKey::Key;
    public const AnnotatedFinal = IsAnnotatedFinalKey::Key;
    public const AnnotatedReadonly = IsAnnotatedReadonlyKey::Key;
    public const ByReference = IsByReferenceKey::Key;
    public const Generator = IsGeneratorKey::Key;
    public const NativeFinal = IsNativeFinalKey::Key;
    public const NativeReadonly = IsNativeReadonlyKey::Key;
    public const Promoted = IsPromotedKey::Key;
    public const Static = IsStaticKey::Key;
    public const Variadic = IsVariadicKey::Key;
    public const InternallyDefined = InternallyDefinedKey::Key;
    public const Methods = MethodsKey::Key;
    public const Parameters = ParametersKey::Key;
    public const PhpDoc = PhpDocKey::Key;
    public const PhpExtension = PhpExtensionKey::Key;
    public const Templates = TemplatesKey::Key;
    public const Aliases = AliasesKey::Key;
    public const Properties = PropertiesKey::Key;
    public const ChangeDetector = ChangeDetectorKey::Key;
    public const Interfaces = InterfacesKey::Key;
    public const Parents = ParentsKey::Key;
    public const ThrowsType = ThrowsTypeKey::Key;
    public const AliasType = AliasTypeKey::Key;
    public const TraitMethodAliases = TraitMethodAliasesKey::Key;
    public const TraitMethodPrecedence = TraitMethodPrecedenceKey::Key;
    public const TypeContext = TypeContextKey::Key;
    public const UnresolvedChangeDetectors = UnresolvedChangeDetectorsKey::Key;
    public const UnresolvedInterfaces = UnresolvedInterfacesKey::Key;
    public const UnresolvedParent = UnresolvedParentKey::Key;
    public const UnresolvedTraits = UnresolvedTraitsKey::Key;
    public const ValueExpression = ValueExpressionKey::Key;
    public const Visibility = VisibilityKey::Key;
    public const Type = TypeKey::Key;
    public const Variance = VarianceKey::Key;
    public const Constraint = ConstraintKey::Key;
    public const UsePhpDocs = UsePhpDocsKey::Key;
}
