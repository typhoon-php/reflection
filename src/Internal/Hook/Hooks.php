<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Hook;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements \IteratorAggregate<ConstantHook|FunctionHook|ClassHook>
 */
final class Hooks implements \IteratorAggregate
{
    /**
     * @var list<ConstantHook>
     */
    private array $constantHooks = [];

    /**
     * @var list<FunctionHook>
     */
    private array $functionHooks = [];

    /**
     * @var list<ClassHook>
     */
    private array $classHooks = [];

    /**
     * @param iterable<ConstantHook|FunctionHook|ClassHook|iterable<ConstantHook|FunctionHook|ClassHook>> $hooks
     */
    public function __construct(iterable $hooks = [])
    {
        foreach ($hooks as $hook) {
            if (is_iterable($hook)) {
                foreach ($hook as $level2Hook) {
                    $this->add($level2Hook);
                }
            } else {
                $this->add($hook);
            }
        }

        usort($this->constantHooks, self::sort(...));
        usort($this->functionHooks, self::sort(...));
        usort($this->classHooks, self::sort(...));
    }

    private function add(ConstantHook|FunctionHook|ClassHook $hook): void
    {
        if ($hook instanceof ConstantHook) {
            $this->constantHooks[] = $hook;
        }

        if ($hook instanceof FunctionHook) {
            $this->functionHooks[] = $hook;
        }

        if ($hook instanceof ClassHook) {
            $this->classHooks[] = $hook;
        }
    }

    private static function sort(ConstantHook|FunctionHook|ClassHook $a, ConstantHook|FunctionHook|ClassHook $b): int
    {
        return $b->priority() <=> $a->priority();
    }

    public function process(ConstantId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        return match (true) {
            $id instanceof ConstantId => $this->processConstant($id, $data, $reflector),
            $id instanceof NamedFunctionId,
            $id instanceof AnonymousFunctionId => $this->processFunction($id, $data, $reflector),
            $id instanceof NamedClassId,
            $id instanceof AnonymousClassId => $this->processClass($id, $data, $reflector),
        };
    }

    public function processConstant(ConstantId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        foreach ($this->constantHooks as $constantHook) {
            $data = $constantHook->processConstant($id, $data, $reflector);
        }

        return $data;
    }

    public function processFunction(NamedFunctionId|AnonymousFunctionId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        foreach ($this->functionHooks as $functionHook) {
            $data = $functionHook->processFunction($id, $data, $reflector);
        }

        return $data;
    }

    public function processClass(NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        foreach ($this->classHooks as $classHook) {
            $data = $classHook->processClass($id, $data, $reflector);
        }

        return $data;
    }

    public function getIterator(): \Generator
    {
        yield from $this->constantHooks;
        yield from $this->functionHooks;
        yield from $this->classHooks;
    }

    public function merge(self $hooks): self
    {
        $copy = clone $this;
        $copy->constantHooks = [...$copy->constantHooks, ...$hooks->constantHooks];
        $copy->functionHooks = [...$copy->functionHooks, ...$hooks->functionHooks];
        $copy->classHooks = [...$copy->classHooks, ...$hooks->classHooks];
        usort($copy->constantHooks, self::sort(...));
        usort($copy->functionHooks, self::sort(...));
        usort($copy->classHooks, self::sort(...));

        return $copy;
    }
}
