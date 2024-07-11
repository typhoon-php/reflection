<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\DeclarationId\NamedClassId;
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

    public function reflect(FunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, DataReflector $reflector): TypedMap
    {
        foreach ($this->hooks as $hook) {
            $data = $hook->reflect($id, $data, $reflector);
        }

        return $data;
    }
}
