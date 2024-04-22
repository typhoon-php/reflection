<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\VariadicPlaceholder;
use Typhoon\TypeContext\TypeContext;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ExpressionCompiler
{
    public function __construct(
        private TypeContext $typeContext = new TypeContext(),
        private readonly string $file = '',
        private readonly string $namespace = '',
        private readonly string $function = '',
        private readonly string $class = '',
        private readonly bool $trait = false,
        private readonly string $method = '',
    ) {}

    /**
     * @return ($expr is null ? null : Expression)
     */
    public function compile(?Expr $expr): ?Expression
    {
        return match (true) {
            $expr === null => null,
            $expr instanceof Scalar\String_,
            $expr instanceof Scalar\Int_,
            $expr instanceof Scalar\Float_ => new Value($expr->value),
            $expr instanceof Expr\Array_ => $this->compileArray($expr),
            $expr instanceof Scalar\MagicConst\Line => new Value($expr->getStartLine()),
            $expr instanceof Scalar\MagicConst\File => new Value($this->file),
            $expr instanceof Scalar\MagicConst\Dir => new Value(\dirname($this->file)),
            $expr instanceof Scalar\MagicConst\Namespace_ => new Value($this->namespace),
            $expr instanceof Scalar\MagicConst\Function_ => new Value($this->function),
            $expr instanceof Scalar\MagicConst\Class_ => new Value($this->class),
            $expr instanceof Scalar\MagicConst\Trait_ => new Value($this->trait ? $this->class : ''),
            $expr instanceof Scalar\MagicConst\Method => new Value($this->method),
            $expr instanceof Coalesce && $expr->left instanceof Expr\ArrayDimFetch => new ArrayFetchCoalesce(
                array: $this->compile($expr->left->var),
                key: $this->compile($expr->left->dim ?? throw new \LogicException()),
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
            default => throw new \LogicException($expr::class),
        };
    }

    private function compileConstant(Name $name): Expression
    {
        $lowerStringName = $name->toLowerString();

        if ($lowerStringName === 'null') {
            return new Value(null);
        }

        if ($lowerStringName === 'true') {
            return new Value(true);
        }

        if ($lowerStringName === 'false') {
            return new Value(false);
        }

        $preResolved = $this->typeContext->preResolveConstantName($name);

        return new ConstantFetch(
            name: $preResolved->name->toStringWithoutSlash(),
            global: $preResolved->global?->toStringWithoutSlash(),
        );
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
            return new Value($this->typeContext->resolveClassName($name)->toString());
        }

        throw new \LogicException();
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
                throw new \LogicException();
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
