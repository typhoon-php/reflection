<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParser;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\VariadicPlaceholder;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\ConstantExpression\ArrayElement;
use Typhoon\Reflection\Internal\ConstantExpression\ArrayExpression;
use Typhoon\Reflection\Internal\ConstantExpression\ArrayFetch;
use Typhoon\Reflection\Internal\ConstantExpression\ArrayFetchCoalesce;
use Typhoon\Reflection\Internal\ConstantExpression\BinaryOperation;
use Typhoon\Reflection\Internal\ConstantExpression\ClassConstantFetch;
use Typhoon\Reflection\Internal\ConstantExpression\ConstantFetch;
use Typhoon\Reflection\Internal\ConstantExpression\Expression;
use Typhoon\Reflection\Internal\ConstantExpression\Instantiation;
use Typhoon\Reflection\Internal\ConstantExpression\Ternary;
use Typhoon\Reflection\Internal\ConstantExpression\UnaryOperation;
use Typhoon\Reflection\Internal\ConstantExpression\Value;
use Typhoon\Reflection\Internal\ConstantExpression\Values;
use Typhoon\Reflection\Internal\Context\Context;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @todo add full anonymous classes and parent::class support
 */
final class ConstantExpressionCompiler
{
    private const ANONYMOUS_FUNCTION_NAME = '{closure}';
    private const ANONYMOUS_CLASS_NAME = '{anonymous-class}';

    public function __construct(
        private readonly Context $context,
    ) {}

    /**
     * @return ($expr is null ? null : Expression)
     */
    public function compile(?Expr $expr): ?Expression
    {
        return match (true) {
            $expr === null => null,
            $expr instanceof Scalar\String_,
            $expr instanceof Scalar\LNumber,
            $expr instanceof Scalar\DNumber => new Value($expr->value),
            $expr instanceof Expr\Array_ => $this->compileArray($expr),
            $expr instanceof Scalar\MagicConst\Line => new Value($expr->getStartLine()),
            $expr instanceof Scalar\MagicConst\File => new Value($this->context->file ?? ''),
            $expr instanceof Scalar\MagicConst\Dir => new Value($this->context->directory() ?? ''),
            $expr instanceof Scalar\MagicConst\Namespace_ => new Value($this->context->namespace()),
            $expr instanceof Scalar\MagicConst\Function_ => $this->function(),
            $expr instanceof Scalar\MagicConst\Class_ => new Value($this->context->self?->name ?? ''),
            $expr instanceof Scalar\MagicConst\Trait_ => new Value($this->context->trait?->name ?? ''),
            $expr instanceof Scalar\MagicConst\Method => $this->method(),
            $expr instanceof Coalesce && $expr->left instanceof Expr\ArrayDimFetch => new ArrayFetchCoalesce(
                array: $this->compile($expr->left->var),
                key: $this->compile($expr->left->dim
                    ?? throw new \LogicException('Unexpected array append operation in a constant expression')),
                default: $this->compile($expr->right),
            ),
            $expr instanceof Expr\BinaryOp => new BinaryOperation(
                left: $this->compile($expr->left),
                right: $this->compile($expr->right),
                operator: $expr->getOperatorSigil(),
            ),
            $expr instanceof Expr\UnaryPlus => new UnaryOperation($this->compile($expr->expr), '+'),
            $expr instanceof Expr\UnaryMinus => new UnaryOperation($this->compile($expr->expr), '-'),
            $expr instanceof Expr\BooleanNot => new UnaryOperation($this->compile($expr->expr), '!'),
            $expr instanceof Expr\BitwiseNot => new UnaryOperation($this->compile($expr->expr), '~'),
            $expr instanceof Expr\ConstFetch => $this->compileConstant($expr->name),
            $expr instanceof Expr\ArrayDimFetch && $expr->dim !== null => new ArrayFetch(
                array: $this->compile($expr->var),
                key: $this->compileIdentifier($expr->dim),
            ),
            $expr instanceof Expr\ClassConstFetch => new ClassConstantFetch(
                class: $this->compileClassName($expr->class),
                name: $this->compileIdentifier($expr->name),
            ),
            $expr instanceof Expr\New_ => new Instantiation(
                class: $this->compileClassName($expr->class),
                arguments: $this->compileArguments($expr->args),
            ),
            $expr instanceof Expr\Ternary => new Ternary(
                condition: $this->compile($expr->cond),
                if: $this->compile($expr->if),
                else: $this->compile($expr->else),
            ),
            default => throw new \LogicException(sprintf('Unsupported expression %s', $expr::class)),
        };
    }

    private function function(): Value
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

        return new Value('');
    }

    private function method(): Value
    {
        $declaration = $this->context->declaration;

        if (!$declaration instanceof MethodId) {
            return new Value('');
        }

        return new Value(sprintf('%s::%s', $declaration->class->name ?? '', $declaration->name));
    }

    private function compileConstant(Name $name): Expression
    {
        $lowerStringName = $name->toLowerString();

        if ($lowerStringName === 'null') {
            return Values::Null;
        }

        if ($lowerStringName === 'true') {
            return Values::True;
        }

        if ($lowerStringName === 'false') {
            return Values::False;
        }

        $namespacedName = $name->getAttribute('namespacedName');

        if ($namespacedName instanceof FullyQualified) {
            return new ConstantFetch(
                namespacedName: $namespacedName->toString(),
                globalName: $name->toString(),
            );
        }

        return new ConstantFetch($name->toString());
    }

    private function compileArray(Expr\Array_ $expr): Expression
    {
        $items = array_values(array_filter($expr->items));

        if ($items === []) {
            return new Value([]);
        }

        return new ArrayExpression(array_map(
            fn(Expr\ArrayItem $item): ArrayElement => new ArrayElement(
                key: $item->unpack ? true : $this->compile($item->key),
                value: $this->compile($item->value),
            ),
            $items,
        ));
    }

    private function compileClassName(Name|Expr|Class_ $name): Expression
    {
        if ($name instanceof Expr) {
            return $this->compile($name);
        }

        if ($name instanceof Name) {
            if ($name->isSpecialClassName()) {
                return match ($name->toLowerString()) {
                    'self' => new Value($this->context->self?->name ?? throw new \LogicException()),
                    'parent' => new Value($this->context->parent?->name ?? throw new \LogicException('Cannot resolve parent class')),
                    'static' => throw new \LogicException('Unexpected static type usage in a constant expression'),
                };
            }

            return new Value($name->toString());
        }

        throw new \LogicException('Unexpected anonymous class in a constant expression');
    }

    private function compileIdentifier(Expr|Identifier $name): Expression
    {
        if ($name instanceof Identifier) {
            return new Value($name->name);
        }

        return $this->compile($name);
    }

    /**
     * @param array<Arg|VariadicPlaceholder> $arguments
     * @return array<Expression>
     */
    private function compileArguments(array $arguments): array
    {
        $compiled = [];

        foreach ($arguments as $argument) {
            if ($argument instanceof VariadicPlaceholder) {
                throw new \LogicException('Unsupported variadic placeholder (...) in a constant expression');
            }

            if ($argument->name === null) {
                $compiled[] = $this->compile($argument->value);

                continue;
            }

            $compiled[$argument->name->name] = $this->compile($argument->value);
        }

        return $compiled;
    }
}
