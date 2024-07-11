<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\DataReflector;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class AddStringableInterface implements ReflectionHook
{
    public function reflect(NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, DataReflector $reflector): TypedMap
    {
        if ($id instanceof NamedFunctionId
            || $id instanceof AnonymousFunctionId
            || $id->name === \Stringable::class
            || !isset($data[Data::Methods]['__toString'])
        ) {
            return $data;
        }

        return $data->modify(Data::UnresolvedInterfaces, static fn(array $interfaces): array => [
            ...$interfaces,
            \Stringable::class => [],
        ]);
    }
}
