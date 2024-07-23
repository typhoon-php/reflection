<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpDoc;

use PHPStan\PhpDocParser\Ast\Attribute;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprArrayItemNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprArrayNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFalseNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNullNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprTrueNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use Typhoon\Reflection\Internal\ConstantExpression\ArrayElement;
use Typhoon\Reflection\Internal\ConstantExpression\ArrayExpression;
use Typhoon\Reflection\Internal\ConstantExpression\ClassConstantFetch;
use Typhoon\Reflection\Internal\ConstantExpression\CompilationContext;
use Typhoon\Reflection\Internal\ConstantExpression\ConstantFetch;
use Typhoon\Reflection\Internal\ConstantExpression\Expression;
use Typhoon\Reflection\Internal\ConstantExpression\Value;
use Typhoon\Reflection\Internal\ConstantExpression\Values;
use Typhoon\Reflection\Internal\Context\Context;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ConstantExpressionCompiler
{
    private readonly CompilationContext $context;

    public function __construct(Context $context)
    {
        $this->context = new CompilationContext($context);
    }

    /**
     * @return ($expr is null ? null : Expression)
     */
    public function compile(?ConstExprNode $expr): ?Expression
    {
        return match (true) {
            $expr === null => null,
            $expr instanceof ConstExprNullNode => Values::Null,
            $expr instanceof ConstExprTrueNode => Values::True,
            $expr instanceof ConstExprFalseNode => Values::False,
            $expr instanceof ConstExprIntegerNode => new Value((int) $expr->value),
            $expr instanceof ConstExprFloatNode => new Value((float) $expr->value),
            $expr instanceof ConstExprStringNode => new Value($expr->value),
            $expr instanceof ConstExprArrayNode => $this->compileArray($expr),
            $expr instanceof ConstFetchNode => $this->compileConstFetch($expr),
            default => throw new \LogicException(sprintf('Unsupported expression %s', $expr::class)),
        };
    }

    private function compileArray(ConstExprArrayNode $expr): Expression
    {
        if ($expr->items === []) {
            return new Value([]);
        }

        return new ArrayExpression(
            array_map(
                fn(ConstExprArrayItemNode $item): ArrayElement => new ArrayElement(
                    key: $item->key === null ? null : $this->compile($item->key),
                    value: $this->compile($item->value),
                ),
                $expr->items,
            ),
        );
    }

    private function compileConstFetch(ConstFetchNode $expr): Expression
    {
        if ($expr->className !== '') {
            return new ClassConstantFetch(
                class: $this->compileClassName($expr->className),
                name: new Value($expr->name),
            );
        }

        return match ($expr->name) {
            '__LINE__' => self::compileNodeLine($expr),
            '__FILE__' => $this->context->magicFile(),
            '__DIR__' => $this->context->magicDir(),
            '__NAMESPACE__' => $this->context->magicNamespace(),
            '__FUNCTION__' => $this->context->magicFunction(),
            '__CLASS__' => $this->context->magicClass(),
            '__TRAIT__' => $this->context->magicTrait(),
            '__METHOD__' => $this->context->magicMethod(),
            default => new ConstantFetch(...$this->context->resolveConstantName($expr->name)),
        };
    }

    /**
     * @param non-empty-string $name
     */
    private function compileClassName(string $name): Expression
    {
        return match (strtolower($name)) {
            'self' => $this->context->self(),
            'parent' => $this->context->parent(),
            'static' => $this->context->static(),
            default => new Value($this->context->resolveClassName($name)),
        };
    }

    /**
     * @return Value<positive-int>
     */
    private static function compileNodeLine(ConstExprNode $expr): Value
    {
        $line = $expr->getAttribute(Attribute::START_LINE);
        \assert(\is_int($line) && $line > 0);

        return new Value($line);
    }
}
