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

    /**
     * @return Expression<string>
     */
    public function magicFile(): Expression
    {
        return Value::from($this->context->file ?? '');
    }

    /**
     * @return Expression<string>
     */
    public function magicDir(): Expression
    {
        return Value::from($this->context->directory() ?? '');
    }

    /**
     * @return Expression<string>
     */
    public function magicNamespace(): Expression
    {
        return Value::from($this->context->namespace());
    }

    /**
     * @return Expression<string>
     */
    public function magicFunction(): Expression
    {
        $id = $this->context->currentId;

        if ($id instanceof NamedFunctionId) {
            return Value::from($id->name);
        }

        if ($id instanceof AnonymousFunctionId) {
            $namespace = $this->context->namespace();

            if ($namespace === '') {
                return Value::from(self::ANONYMOUS_FUNCTION_NAME);
            }

            return Value::from($namespace . '\\' . self::ANONYMOUS_FUNCTION_NAME);
        }

        if ($id instanceof MethodId) {
            return Value::from($id->name);
        }

        return Value::from('');
    }

    /**
     * @return Expression<string>
     */
    public function magicClass(): Expression
    {
        if ($this->context->self !== null) {
            // todo anonymous
            return Value::from($this->context->self->name ?? throw new \LogicException('anonymous'));
        }

        if ($this->context->trait !== null) {
            return new MagicClassInTrait($this->context->trait->name);
        }

        return Value::from('');
    }

    /**
     * @return Expression<string>
     */
    public function magicTrait(): Expression
    {
        return Value::from($this->context->trait?->name ?? '');
    }

    /**
     * @return Expression<string>
     */
    public function magicMethod(): Expression
    {
        $id = $this->context->currentId;

        if (!$id instanceof MethodId) {
            return Value::from('');
        }

        return Value::from(sprintf('%s::%s', $id->class->name ?? '', $id->name));
    }

    /**
     * @return Expression<non-empty-string>
     */
    public function self(): Expression
    {
        if ($this->context->self !== null) {
            return new SelfClass($this->context->self);
        }

        if ($this->context->trait !== null) {
            return new SelfClassInTrait($this->context->trait);
        }

        throw new \LogicException('Not in a class!');
    }

    /**
     * @return Expression<non-empty-string>
     */
    public function parent(): Expression
    {
        if ($this->context->parent !== null) {
            return new ParentClass($this->context->parent);
        }

        if ($this->context->trait !== null) {
            return ParentClassInTrait::Instance;
        }

        throw new \LogicException('No parent!');
    }

    public function static(): never
    {
        throw new \LogicException('Unexpected static type usage in a constant expression');
    }
}
