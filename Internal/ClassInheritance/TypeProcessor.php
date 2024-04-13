<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ClassInheritance;

use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\ClassInheritance
 */
final class TypeProcessor
{
    /**
     * @param list<TypeVisitor<Type>> $processors
     */
    public function __construct(
        private readonly array $processors,
    ) {}

    public function process(Type $type): Type
    {
        foreach ($this->processors as $processor) {
            $type = $type->accept($processor);
        }

        return $type;
    }
}
