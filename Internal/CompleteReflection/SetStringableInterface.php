<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\ClassHook;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum SetStringableInterface implements ClassHook
{
    case Instance;

    public function process(NamedClassId|AnonymousClassId $id, TypedMap $data): TypedMap
    {
        if ($id->name === \Stringable::class || !isset($data[Data::Methods]['__toString'])) {
            return $data;
        }

        return $data->with(Data::UnresolvedInterfaces, [
            ...$data[Data::UnresolvedInterfaces],
            \Stringable::class => [],
        ]);
    }
}
