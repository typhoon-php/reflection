<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\ClassKind;
use Typhoon\Reflection\Internal\Hook\ClassHook;
use Typhoon\Reflection\Internal\Hook\HookPriorities;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum CopyPromotedParameterToProperty implements ClassHook
{
    case Instance;

    public function priority(): int
    {
        return HookPriorities::COMPLETE_REFLECTION;
    }

    public function processClass(NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        $classKind = $data[Data::ClassKind];

        if ($classKind === ClassKind::Enum || $classKind === ClassKind::Interface) {
            return $data;
        }

        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        $constructor = $data[Data::Methods]['__construct'] ?? null;

        if ($constructor === null) {
            return $data;
        }

        $parameters = $constructor[Data::Parameters];
        $properties = $data[Data::Properties];

        foreach ($parameters as $name => $parameter) {
            if ($parameter[Data::Promoted]) {
                $parameters[$name] = $parameter->without(Data::NativeReadonly, Data::AnnotatedReadonly, Data::Visibility);
                $properties[$name] = $parameter->without(Data::DefaultValueExpression);
            }
        }

        return $data
            ->with(Data::Methods, [
                ...$data[Data::Methods],
                '__construct' => $constructor->with(Data::Parameters, $parameters),
            ])
            ->with(Data::Properties, $properties);
    }
}
