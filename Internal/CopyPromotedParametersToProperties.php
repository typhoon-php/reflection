<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class CopyPromotedParametersToProperties implements ReflectionHook
{
    public function reflect(ClassId|AnonymousClassId|FunctionId $id, TypedMap $data): TypedMap
    {
        if ($id instanceof FunctionId) {
            return $data;
        }

        $classKind = $data[Data::ClassKind()];

        if ($classKind === ClassKind::Enum || $classKind === ClassKind::Interface) {
            return $data;
        }

        $methods = $data[Data::Methods()];

        if (!isset($methods['__construct'])) {
            return $data;
        }

        $properties = $data[Data::Properties()];

        foreach ($methods['__construct'][Data::Parameters()] as $name => $parameter) {
            if ($parameter[Data::Promoted()]) {
                $properties[$name] = $parameter;
            }
        }

        return $data->with(Data::Properties(), $properties);
    }
}
