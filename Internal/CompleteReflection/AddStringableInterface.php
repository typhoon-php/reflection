<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\ClassReflectionHook;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\DataReflector;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class AddStringableInterface implements ClassReflectionHook
{
    public function process(NamedClassId|AnonymousClassId $id, TypedMap $data, DataReflector $reflector): TypedMap
    {
        if ($id->name === \Stringable::class || !isset($data[Data::Methods]['__toString'])) {
            return $data;
        }

        return $data->modify(Data::UnresolvedInterfaces, static fn(array $interfaces): array => [
            ...$interfaces,
            \Stringable::class => [],
        ]);
    }
}
