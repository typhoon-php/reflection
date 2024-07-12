<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\VariadicPlaceholder;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @todo add full anonymous classes and parent::class support
 */
final class ConstantExpressionCompiler
{
    private const ANONYMOUS_FUNCTION_NAME = '{closure}';
    private const ANONYMOUS_CLASS_NAME = '{anonymous-class}';

    /**
     * @param Expression<string> $file
     * @param Expression<string> $namespace
     * @param Expression<string> $function
     * @param Expression<string> $class
     * @param ?Expression<non-empty-string> $parent
     * @param Expression<string> $trait
     * @param Expression<string> $method
     */
    private function __construct(
        private readonly Expression $file,
        private readonly Expression $namespace = new Value(''),
        private Expression $function = new Value(''),
        private Expression $class = new Value(''),
        private ?Expression $parent = null,
        private Expression $trait = new Value(''),
        private Expression $method = new Value(''),
    ) {}

    /**
     * @param ?non-empty-string $file
     */
    public static function start(?string $file): self
    {
        return new self(new Value($file ?? ''));
    }

    /**
     * @param ?non-empty-string $namespace
     */
    public function enterNamespace(?string $namespace): self
    {
        return new self(
            file: $this->file,
            namespace: new Value($namespace ?? ''),
        );
    }

    /**
     * @param non-empty-string $name
     */
    public function enterFunction(string $name): self
    {
        $compiler = clone $this;
        $compiler->function = new Value($name);

        return $compiler;
    }

    public function enterAnonymousFunction(): self
    {
        $compiler = clone $this;
        $namespaceString = $this->namespace->evaluate();
        $compiler->function = new Value(($namespaceString === '' ? '' : $namespaceString . '\\') . self::ANONYMOUS_FUNCTION_NAME);

        return $compiler;
    }

    /**
     * @param non-empty-string $class
     * @param ?non-empty-string $parent
     */
    public function enterClass(string $class, ?string $parent, bool $trait): self
    {
        $compiler = clone $this;
        $compiler->class = new Value($class);
        $compiler->parent = $parent === null ? null : new Value($parent);
        $compiler->trait = $trait ? $compiler->class : new Value('');
        $compiler->method = new Value('');

        return $compiler;
    }

    /**
     * @param ?non-empty-string $parent
     */
    public function enterAnonymousClass(?string $parent): self
    {
        $compiler = clone $this;
        $compiler->class = new Value(self::ANONYMOUS_CLASS_NAME);
        $compiler->parent = $parent === null ? null : new Value($parent);
        $compiler->trait = new Value('');
        $compiler->method = new Value('');

        return $compiler;
    }

    /**
     * @param non-empty-string $name
     */
    public function enterMethod(string $name): self
    {
        $compiler = clone $this;
        $compiler->method = new Value($this->class->evaluate() . '::' . $name);

        return $compiler;
    }

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
            $expr instanceof Scalar\MagicConst\File => $this->file,
            $expr instanceof Scalar\MagicConst\Dir => new Value(\dirname($this->file->evaluate())),
            $expr instanceof Scalar\MagicConst\Namespace_ => $this->namespace,
            $expr instanceof Scalar\MagicConst\Function_ => $this->function,
            $expr instanceof Scalar\MagicConst\Class_ => $this->class,
            $expr instanceof Scalar\MagicConst\Trait_ => $this->trait,
            $expr instanceof Scalar\MagicConst\Method => $this->method,
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
                name: $namespacedName->toString(),
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
                    'self' => $this->class,
                    'parent' => $this->parent ?? throw new \LogicException('Cannot resolve parent class'),
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
