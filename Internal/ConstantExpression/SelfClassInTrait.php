<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<non-empty-string>
 */
final class SelfClassInTrait implements Expression
{
    public function __construct(
        private readonly NamedClassId $trait,
    ) {}

    public function recompile(CompilationContext $context): Expression
    {
        return $context->self();
    }

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        return $this->trait->name;
    }
}
