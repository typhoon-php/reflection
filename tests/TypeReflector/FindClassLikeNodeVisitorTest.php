<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeReflector;

use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser\Php7;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(FindClassLikeNodeVisitor::class)]
final class FindClassLikeNodeVisitorTest extends TestCase
{
    public function testItFindsExistingClass(): void
    {
        $visitor = new FindClassLikeNodeVisitor(self::class);

        $this->parseAndTraverse(file_get_contents(__FILE__), $visitor);

        self::assertNotNull($visitor->node);
    }

    public function testItDoesNotFindNonExistingClass(): void
    {
        $visitor = new FindClassLikeNodeVisitor(parent::class);

        $this->parseAndTraverse(file_get_contents(__FILE__), $visitor);

        self::assertNull($visitor->node);
    }

    public function testItClearsNodeBetweenTraversals(): void
    {
        $visitor = new FindClassLikeNodeVisitor(self::class);

        $this->parseAndTraverse(file_get_contents(__FILE__), $visitor);
        $this->parseAndTraverse('', $visitor);

        self::assertNull($visitor->node);
    }

    private function parseAndTraverse(string $code, FindClassLikeNodeVisitor $visitor): void
    {
        $parser = new Php7(new Emulative());
        $statements = $parser->parse($code) ?? [];
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);
    }
}
