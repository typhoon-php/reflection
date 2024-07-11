<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ReflectionHooks implements ReflectionHook
{
    /**
     * @param list<ReflectionHook> $hooks
     */
    public function __construct(
        private readonly array $hooks,
    ) {}

    public function reflect(NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, DataReflector $reflector): TypedMap
    {
        foreach ($this->hooks as $hook) {
            $data = $hook->reflect($id, $data, $reflector);
        }

        return $data;
    }
}
