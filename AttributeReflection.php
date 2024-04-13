<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\DeclarationId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Expression\Expression;
use Typhoon\Reflection\Internal\NativeAdapter\AttributeAdapter;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\classId;

/**
 * @api
 * @readonly
 */
final class AttributeReflection
{
    public function __construct(
        public readonly DeclarationId $declaredAt,
        private readonly TypedMap $data,
        private readonly Reflector $reflector,
    ) {}

    /**
     * @return non-empty-string
     */
    public function className(): string
    {
        return $this->data[Data::AttributeClass()];
    }

    public function class(): ClassReflection
    {
        return $this->reflector->reflect(classId($this->className()));
    }

    public function isRepeated(): bool
    {
        return $this->data[Data::Repeated()];
    }

    public function arguments(): array
    {
        return array_map(
            fn(Expression $expression): mixed => $expression->evaluate($this->reflector),
            $this->data[Data::ArgumentExpressions()],
        );
    }

    public function newInstance(): object
    {
        /** @psalm-suppress InvalidStringClass */
        return new ($this->className())(...$this->arguments());
    }

    public function toNative(): \ReflectionAttribute
    {
        return new AttributeAdapter($this);
    }
}
