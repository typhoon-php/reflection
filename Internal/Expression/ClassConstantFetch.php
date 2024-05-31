<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\DeclarationId\MethodId;
use Typhoon\Reflection\ClassConstantReflection;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\MethodReflection;
use Typhoon\Reflection\ParameterReflection;
use Typhoon\Reflection\PropertyReflection;
use Typhoon\Reflection\Reflection;
use Typhoon\Reflection\Reflector;
use function Typhoon\DeclarationId\namedClassId;

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

    /**
     * @return non-empty-string
     */
    public function name(Reflection $reflection, Reflector $reflector): string
    {
        $name = $this->name->evaluate($reflection, $reflector);
        \assert(\is_string($name));
        $class = match (true) {
            $this->class === MagicClass::Constant => 'self',
            $reflection instanceof ClassReflection => $reflection->name,
            $reflection instanceof ClassConstantReflection,
            $reflection instanceof PropertyReflection,
            $reflection instanceof MethodReflection => $reflection->id->class->name,
            $reflection instanceof ParameterReflection => $reflection->id->function instanceof MethodId
                ? $reflection->id->function->class->name
                : throw new \LogicException(),
            default => throw new \LogicException(),
        };

        return $class . '::' . $name;
    }

    public function evaluate(Reflection $reflection, Reflector $reflector): mixed
    {
        $name = $this->name->evaluate($reflection, $reflector);
        \assert(\is_string($name));

        return $this->classReflection($reflection, $reflector)->constants[$name]->value();
    }

    private function classReflection(Reflection $reflection, Reflector $reflector): ClassReflection
    {
        // todo check no cycles
        if ($this->class !== MagicClass::Constant) {
            $class = $this->class->evaluate($reflection, $reflector);
            \assert(\is_string($class));

            return $reflector->reflect(namedClassId($class));
        }

        return match (true) {
            $reflection instanceof ClassReflection => $reflection,
            $reflection instanceof ClassConstantReflection,
            $reflection instanceof PropertyReflection,
            $reflection instanceof MethodReflection => $reflection->class(),
            $reflection instanceof ParameterReflection => $reflection->class() ?? throw new \LogicException(),
            default => throw new \LogicException(),
        };
    }
}
