<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Context\Context;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @todo add full anonymous classes and parent::class support
 */
final class CompilationContext
{
    private const ANONYMOUS_FUNCTION_NAME = '{closure}';

    public function __construct(
        private readonly Context $context,
    ) {}

    /**
     * @param non-empty-string $unresolvedName
     * @return array{non-empty-string, ?non-empty-string}
     */
    public function resolveConstantName(string $unresolvedName): array
    {
        return $this->context->resolveConstantName($unresolvedName);
    }

    /**
     * @param non-empty-string $unresolvedName
     * @return non-empty-string
     */
    public function resolveClassName(string $unresolvedName): string
    {
        return $this->context->resolveClassName($unresolvedName);
    }

    public function magicFile(): Value
    {
        return new Value($this->context->file ?? '');
    }

    public function magicDir(): Value
    {
        return new Value($this->context->directory() ?? '');
    }

    public function magicNamespace(): Value
    {
        return new Value($this->context->namespace());
    }

    public function magicFunction(): Value
    {
        $declaration = $this->context->declaration;

        if ($declaration instanceof NamedFunctionId) {
            return new Value($declaration->name);
        }

        if ($declaration instanceof AnonymousFunctionId) {
            $namespace = $this->context->namespace();

            if ($namespace === '') {
                return new Value(self::ANONYMOUS_FUNCTION_NAME);
            }

            return new Value($namespace . '\\' . self::ANONYMOUS_FUNCTION_NAME);
        }

        if ($declaration instanceof MethodId) {
            return new Value($declaration->name);
        }

        return new Value('');
    }

    public function magicClass(): Value|TraitSelf
    {
        if ($this->context->self !== null) {
            // todo anonymous
            return new Value($this->context->self->name ?? '');
        }

        if ($this->context->trait !== null) {
            return new TraitSelf($this->context->trait->name);
        }

        return new Value('');
    }

    public function magicTrait(): Value
    {
        return new Value($this->context->trait?->name ?? '');
    }

    public function magicMethod(): Value
    {
        $declaration = $this->context->declaration;

        if (!$declaration instanceof MethodId) {
            return new Value('');
        }

        return new Value(sprintf('%s::%s', $declaration->class->name ?? '', $declaration->name));
    }

    public function self(): Value|TraitSelf
    {
        if ($this->context->self !== null) {
            // todo anonymous
            return new Value($this->context->self->name ?? '');
        }

        if ($this->context->trait !== null) {
            return new TraitSelf($this->context->trait->name);
        }

        throw new \LogicException('No self!');
    }

    public function parent(): Value|TraitParent
    {
        if ($this->context->parent !== null) {
            return new Value($this->context->parent->name);
        }

        if ($this->context->trait !== null) {
            return TraitParent::Instance;
        }

        throw new \LogicException('No parent!');
    }

    public function static(): never
    {
        throw new \LogicException('Unexpected static type usage in a constant expression');
    }
}
