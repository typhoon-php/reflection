<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum MagicConstant implements Expression
{
    case File;
    case Dir;
    case Namespace;
    case Function;
    case Class_;
    case Trait;
    case Method;

    public function evaluate(EvaluationContext $context): mixed
    {
        return match ($this) {
            self::File => $context->file,
            self::Dir => $context->directory(),
            self::Namespace => $context->namespace,
            self::Function => $context->function,
            self::Class_ => $context->class,
            self::Trait => $context->trait(),
            self::Method => $context->method,
        };
    }
}
