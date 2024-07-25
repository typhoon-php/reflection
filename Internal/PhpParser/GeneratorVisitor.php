<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParser;

use PhpParser\Node;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\FunctionLike;
use PhpParser\NodeVisitorAbstract;
use function Typhoon\Reflection\Internal\array_value_last;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class GeneratorVisitor extends NodeVisitorAbstract
{
    private const ATTRIBUTE = 'is_generator';

    /**
     * @var list<FunctionLike>
     */
    private array $functionStack = [];

    public static function isGenerator(FunctionLike $function): bool
    {
        $attribute = $function->getAttribute(self::ATTRIBUTE);

        if (\is_bool($attribute)) {
            return $attribute;
        }

        throw new \LogicException(\sprintf('%s was not used during traversal', self::class));
    }

    public function beforeTraverse(array $nodes): ?array
    {
        $this->functionStack = [];

        return null;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof FunctionLike) {
            $node->setAttribute(self::ATTRIBUTE, false);
            $this->functionStack[] = $node;

            return null;
        }

        if ($node instanceof Yield_) {
            array_value_last($this->functionStack)?->setAttribute(self::ATTRIBUTE, true);

            return null;
        }

        return null;
    }

    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof FunctionLike) {
            array_pop($this->functionStack);

            return null;
        }

        return null;
    }
}
