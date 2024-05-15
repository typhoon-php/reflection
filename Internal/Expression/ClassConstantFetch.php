<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\ClassConstantReflection;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\MethodReflection;
use Typhoon\Reflection\ParameterReflection;
use Typhoon\Reflection\PropertyReflection;
use Typhoon\Reflection\Reflection;
use Typhoon\Reflection\Reflector;
use function Typhoon\DeclarationId\anyClassId;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ClassConstantFetch implements Expression
{
    public function __construct(
        private readonly Expression $class,
        private readonly Expression $name,
    ) {}

    public function evaluate(Reflection $reflection, Reflector $reflector): mixed
    {
        $name = $this->name->evaluate($reflection, $reflector);
        \assert(\is_string($name));

        if ($this->class === MagicClass::Constant) {
            // todo prevent infinite loops
            if ($reflection instanceof ClassConstantReflection && $reflection->name === $name) {
                return $reflection->value();
            }

            $classReflection = match (true) {
                $reflection instanceof ClassReflection => $reflection,
                $reflection instanceof ClassConstantReflection => $reflection->class(),
                $reflection instanceof PropertyReflection => $reflection->class(),
                $reflection instanceof MethodReflection => $reflection->class(),
                $reflection instanceof ParameterReflection => $reflection->class() ?? throw new \LogicException(),
                default => throw new \LogicException(),
            };
        } else {
            $class = $this->class->evaluate($reflection, $reflector);
            \assert(\is_string($class));
            $classReflection = $reflector->reflect(anyClassId($class));
        }

        return $classReflection->constants[$name]->value();
    }
}
