<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\Reflection\Reflector;
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

    public function reflect(ClassId|AnonymousClassId|FunctionId $id, TypedMap $data, Reflector $reflector): TypedMap
    {
        foreach ($this->hooks as $hook) {
            $data = $hook->reflect($id, $data, $reflector);
        }

        return $data;
    }
}
