<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\Internal\ClassReflector;

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

    public function evaluate(ClassReflector $classReflector): mixed
    {
        // TODO return $context->reflectClass($this->class->evaluate($context))->;
        /** @psalm-suppress MixedArgument */
        return \constant(sprintf('%s::%s', $this->class->evaluate($classReflector), $this->name->evaluate($classReflector)));
    }
}
