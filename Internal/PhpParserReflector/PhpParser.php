<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParserReflector;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PhpParser
{
    private readonly Parser $parser;

    public function __construct(?Parser $parser = null)
    {
        $this->parser = $parser ?? (new ParserFactory())->createForHostVersion();
    }

    /**
     * @param array<Node> $nodes
     * @param non-empty-list<NodeVisitor> $visitors
     */
    public static function traverse(array $nodes, array $visitors): void
    {
        $traverser = new NodeTraverser();

        foreach ($visitors as $visitor) {
            $traverser->addVisitor($visitor);
        }

        $traverser->traverse($nodes);
    }

    /**
     * @return array<Node>
     */
    public function parse(string $code): array
    {
        return $this->parser->parse($code) ?? throw new Error('Failed to parse code');
    }
}
