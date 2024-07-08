<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\ClassConstantId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\DeclarationId\PropertyId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Expression\Expression;
use Typhoon\Reflection\Internal\NativeAdapter\AttributeAdapter;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 * @readonly
 */
final class AttributeReflection
{
    /**
     * @param non-negative-int $index
     */
    public function __construct(
        public readonly FunctionId|ParameterId|ClassId|ClassConstantId|MethodId|PropertyId $targetId,
        private readonly int $index,
        public readonly TypedMap $data,
        private readonly Reflector $reflector,
    ) {}

    /**
     * @return non-empty-string
     */
    public function className(): string
    {
        return $this->data[Data::AttributeClassName];
    }

    public function class(): ClassReflection
    {
        return $this->reflector->reflect(Id::namedClass($this->className()));
    }

    public function target(): Reflection
    {
        return $this->reflector->reflect($this->targetId);
    }

    public function isRepeated(): bool
    {
        return $this->data[Data::AttributeRepeated];
    }

    public function arguments(): array
    {
        return array_map(
            fn(Expression $expression): mixed => $expression->evaluate($this->reflector),
            $this->data[Data::ArgumentExpressions],
        );
    }

    public function newInstance(): object
    {
        /** @psalm-suppress InvalidStringClass */
        return new ($this->className())(...$this->arguments());
    }

    public function toNative(): \ReflectionAttribute
    {
        return new AttributeAdapter($this, $this->index);
    }
}
