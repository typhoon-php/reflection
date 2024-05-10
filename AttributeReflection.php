<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\DeclarationId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Expression\Expression;
use Typhoon\Reflection\Internal\NativeAdapter\AttributeAdapter;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\anyClassId;

/**
 * @api
 * @readonly
 */
final class AttributeReflection
{
    /**
     * @var non-empty-string
     */
    public readonly string $name;

    public function __construct(
        public readonly DeclarationId $targetId,
        public readonly TypedMap $data,
        private readonly Reflector $reflector,
    ) {
        $this->name = $this->data[Data::AttributeClass()];
    }

    public function class(): ClassReflection
    {
        return $this->reflector->reflect(anyClassId($this->name));
    }

    public function target(): Reflection
    {
        return $this->reflector->reflect($this->targetId);
    }

    public function isRepeated(): bool
    {
        return $this->data[Data::Repeated()];
    }

    public function arguments(): array
    {
        return array_map(
            fn(Expression $expression): mixed => $expression->evaluate($this->target(), $this->reflector),
            $this->data[Data::ArgumentExpressions()],
        );
    }

    public function newInstance(): object
    {
        /** @psalm-suppress InvalidStringClass */
        return new ($this->name)(...$this->arguments());
    }

    public function toNative(): \ReflectionAttribute
    {
        return new AttributeAdapter($this);
    }
}
