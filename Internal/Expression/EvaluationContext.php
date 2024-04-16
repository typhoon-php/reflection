<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-suppress PossiblyUnusedProperty
 */
final class EvaluationContext
{
    /**
     * @var callable(string): mixed
     */
    private readonly mixed $constant;

    /**
     * @var callable(string, string): mixed
     */
    private readonly mixed $classConstant;

    /**
     * @param ?callable(string): mixed $constant
     * @param ?callable(string, string): mixed $classConstant
     */
    public function __construct(
        ?callable $constant = null,
        ?callable $classConstant = null,
        public readonly string $file = '',
        public readonly string $namespace = '',
        public readonly string $function = '',
        public readonly string $class = '',
        private readonly bool $trait = false,
        public readonly string $method = '',
    ) {
        $this->constant = $constant ?? 'constant';
        $this->classConstant = $classConstant ?? static fn(string $class, string $name): mixed => \constant(sprintf('%s::%s', $class, $name));
    }

    public function directory(): string
    {
        return \dirname($this->file);
    }

    public function trait(): string
    {
        return $this->trait ? $this->class : '';
    }

    public function constant(string $name): mixed
    {
        return ($this->constant)($name);
    }

    public function classConstant(string $class, string $name): mixed
    {
        return ($this->classConstant)($class, $name);
    }
}
