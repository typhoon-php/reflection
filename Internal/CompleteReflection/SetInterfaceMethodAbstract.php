<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\ClassHook;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\ClassKind;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum SetInterfaceMethodAbstract implements ClassHook
{
    case Instance;

    public function process(NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        if ($data[Data::ClassKind] !== ClassKind::Interface) {
            return $data;
        }

        return $data->with(Data::Methods, array_map(
            static fn(TypedMap $method): TypedMap => $method->with(Data::Abstract, true),
            $data[Data::Methods],
        ));
    }
}
