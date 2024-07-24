<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<non-empty-string>
 */
final class SelfClass implements Expression
{
    public function __construct(
        private readonly NamedClassId|AnonymousClassId $class,
    ) {}

    public function recompile(CompilationContext $context): Expression
    {
        return $context->self();
    }

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        return $this->class->name ?? throw new \LogicException('Anonymous!');
    }
}
