<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\ClassHook;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\ClassKind;
use Typhoon\Reflection\Internal\Data\Visibility;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum SetClassCloneable implements ClassHook
{
    case Instance;

    public function process(NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        if (isset($data[Data::Cloneable])) {
            return $data;
        }

        if ($data[Data::ClassKind] !== ClassKind::Class_ || $data[Data::Abstract]) {
            return $data;
        }

        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        $clone = $data[Data::Methods]['__clone'] ?? null;

        if ($clone !== null && $clone[Data::Visibility] !== Visibility::Public) {
            return $data;
        }

        return $data->with(Data::Cloneable, true);
    }
}
