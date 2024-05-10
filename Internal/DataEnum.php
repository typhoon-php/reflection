<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\TypedMap\Key;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @template T
 * @implements Key<T>
 */
enum DataEnum implements Key
{
    case ByReference;
    case NativeReadonly;
    case AnnotatedReadonly;
    case Static;
    case NativeFinal;
    case AnnotatedFinal;
    case Abstract;
    case Generator;
    case Variadic;
    case Promoted;
    case WrittenInC;
    case Node;
    case ClassKind;
    case StartLine;
    case EndLine;
    case Attributes;
    case ClassConstants;
    case Properties;
    case Methods;
    case Parameters;
    case File;
    case Extension;
    case PhpDoc;
    case Visibility;
    case DefaultValueExpression;
    case ValueExpression;
    case BackingValueExpression;
    case NativeType;
    case TentativeType;
    case AnnotatedType;
    case ResolvedType;
    case TypeContext;
    case UnresolvedParent;
    case UnresolvedInterfaces;
    case UnresolvedTraits;
    case AttributeClass;
    case Index;
    case ArgumentExpressions;
    case Repeated;
    case EnumCase;
    case TraitMethodAliases;
    case TraitMethodPrecedence;
    case ResolvedInterfaces;
    case ResolvedParents;
    case DeclarationId;
    case UnresolvedChangeDetectors;
    case ResolvedChangeDetector;
    case ThrowsType;
}
