<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\PHPDocParser;

use ExtendedTypeSystem\Reflection\TagPrioritizer;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser as PHPStanPhpDocParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\anything;
use function PHPUnit\Framework\never;

/**
 * @internal
 */
#[CoversClass(PHPDocParser::class)]
final class PHPDocParserTest extends TestCase
{
    public function testNothingIsCalledForNodeWithoutPHPDoc(): void
    {
        $parser = $this->createMock(PHPStanPhpDocParser::class);
        $parser->expects(never())->method(anything());
        $lexer = $this->createMock(Lexer::class);
        $lexer->expects(never())->method(anything());
        $tagPrioritizer = $this->createMock(TagPrioritizer::class);
        $tagPrioritizer->expects(never())->method(anything());
        $phpDocParser = new PHPDocParser(
            parser: $parser,
            lexer: $lexer,
            tagPrioritizer: $tagPrioritizer,
        );
        $node = $this->createStub(Node::class);
        $node->method('getDocComment')->willReturn(null);

        $phpDocParser->parse($node);
    }

    public function testNothingIsCalledForNodeWithEmptyPHPDoc(): void
    {
        $parser = $this->createMock(PHPStanPhpDocParser::class);
        $parser->expects(never())->method(anything());
        $lexer = $this->createMock(Lexer::class);
        $lexer->expects(never())->method(anything());
        $tagPrioritizer = $this->createMock(TagPrioritizer::class);
        $tagPrioritizer->expects(never())->method(anything());
        $phpDocParser = new PHPDocParser(
            parser: $parser,
            lexer: $lexer,
            tagPrioritizer: $tagPrioritizer,
        );
        $node = $this->createStub(Node::class);
        $node->method('getDocComment')->willReturn(new Doc(' '));

        $phpDocParser->parse($node);
    }

    public function testItParsesPHPDoc(): void
    {
        $phpDocParser = new PHPDocParser();
        $node = $this->createStub(Node::class);
        $node->method('getDocComment')->willReturn(new Doc(
            <<<'PHP'
                /**
                 * @var string
                 */
                PHP,
        ));

        $phpDoc = $phpDocParser->parse($node);

        self::assertEquals(new IdentifierTypeNode('string'), $phpDoc->varType());
    }

    public function testItPrioritizesTags(): void
    {
        $phpDocParser = new PHPDocParser();
        $node = $this->createStub(Node::class);
        $node->method('getDocComment')->willReturn(new Doc(
            <<<'PHP'
                /**
                 * @var string
                 * @psalm-var int
                 */
                PHP,
        ));

        $phpDoc = $phpDocParser->parse($node);

        self::assertEquals(new IdentifierTypeNode('int'), $phpDoc->varType());
    }
}
