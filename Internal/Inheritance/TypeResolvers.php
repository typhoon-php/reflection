<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Inheritance;

use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\Inheritance
 */
final class TypeResolvers
{
    /**
     * @param list<TypeVisitor<Type>> $resolvers
     */
    public function __construct(
        private readonly array $resolvers,
    ) {}

    public function resolve(Type $type): Type
    {
        foreach ($this->resolvers as $resolver) {
            $type = $type->accept($resolver);
        }

        return $type;
    }
}
