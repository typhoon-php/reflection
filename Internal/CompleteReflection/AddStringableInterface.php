<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class AddStringableInterface implements ReflectionHook
{
    public function reflect(FunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data): TypedMap
    {
        if ($id instanceof FunctionId
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
