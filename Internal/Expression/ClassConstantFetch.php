<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

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
    public function class(?Reflector $reflector = null): string
    {
        $class = $this->class->evaluate($reflector);
        \assert(\is_string($class) && $class !== '');

        return $class;
    }

    /**
     * @return non-empty-string
     */
    public function name(?Reflector $reflector = null): string
    {
        $name = $this->name->evaluate($reflector);
        \assert(\is_string($name) && $name !== '');

        return $name;
    }

    public function evaluate(?Reflector $reflector = null): mixed
    {
        $class = $this->class($reflector);
        $name = $this->name($reflector);

        if ($name === 'class') {
            return $class;
        }

        if ($reflector === null) {
            return \constant($class . '::' . $name);
        }

        return $reflector->reflect(namedClassId($class))->constants()[$name]->value();
    }
}
