<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ReflectionHooks
{
    /**
     * @var list<ConstantReflectionHook>
     */
    private array $constantHooks = [];

    /**
     * @var list<FunctionReflectionHook>
     */
    private array $functionHooks = [];

    /**
     * @var list<ClassReflectionHook>
     */
    private array $classHooks = [];

    /**
     * @param iterable<ConstantReflectionHook|FunctionReflectionHook|ClassReflectionHook> $hooks
     */
    public function __construct(iterable $hooks)
    {
        foreach ($hooks as $hook) {
            if ($hook instanceof ConstantReflectionHook) {
                $this->constantHooks[] = $hook;
            }

            if ($hook instanceof FunctionReflectionHook) {
                $this->functionHooks[] = $hook;
            }

            if ($hook instanceof ClassReflectionHook) {
                $this->classHooks[] = $hook;
            }
        }
    }

    public function process(ConstantId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, Reflector $reflector): TypedMap
    {
        $hooks = match (true) {
            $id instanceof ConstantId => $this->constantHooks,
            $id instanceof NamedFunctionId,
            $id instanceof AnonymousFunctionId => $this->functionHooks,
            $id instanceof NamedClassId,
            $id instanceof AnonymousClassId => $this->classHooks,
        };

        foreach ($hooks as $hook) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $data = $hook->process($id, $data, $reflector);
        }

        return $data;
    }
}
