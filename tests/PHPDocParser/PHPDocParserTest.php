<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\PHPDocParser;

use ExtendedTypeSystem\Reflection\Variance;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
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
#[CoversClass(PHPDoc::class)]
final class PHPDocParserTest extends TestCase
{
    public function testNothingIsCalledForNodeWithoutPHPDoc(): void
    {
        $parser = $this->createMock(PHPStanPhpDocParser::class);
        $parser->expects(never())->method(anything());
        $lexer = $this->createMock(Lexer::class);
        $lexer->expects(never())->method(anything());
        $phpDocParser = new PHPDocParser($parser, $lexer);
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
        $phpDocParser = new PHPDocParser($parser, $lexer);
        $node = $this->createNodeWithPHPDoc(' ');

        $phpDocParser->parse($node);
    }

    public function testItReturnsNullVarTypeWhenNoVarTag(): void
    {
        $parser = new PHPDocParser();
        $node = $this->createNodeWithPHPDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        );

        $varType = $parser->parse($node)->varType();

        self::assertNull($varType);
    }

    public function testItReturnsLatestPrioritizedVarTagType(): void
    {
        $parser = new PHPDocParser();
        $node = $this->createNodeWithPHPDoc(
            <<<'PHP'
                /**
                 * @example
                 * @var int
                 * @psalm-var float
                 * @psalm-var string
                 */
                PHP,
        );

        $varType = $parser->parse($node)->varType();

        self::assertEquals(new IdentifierTypeNode('string'), $varType);
    }

    public function testItReturnsNullParamTypeWhenNoParamTag(): void
    {
        $parser = new PHPDocParser();
        $node = $this->createNodeWithPHPDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        );

        $paramType = $parser->parse($node)->paramType('a');

        self::assertNull($paramType);
    }

    public function testItReturnsLatestPrioritizedParamTagType(): void
    {
        $parser = new PHPDocParser();
        $node = $this->createNodeWithPHPDoc(
            <<<'PHP'
                /**
                 * @example
                 * @param int $a
                 * @psalm-param float $a
                 * @psalm-param string $a
                 * @psalm-param object $b
                 */
                PHP,
        );

        $paramType = $parser->parse($node)->paramType('a');

        self::assertEquals(new IdentifierTypeNode('string'), $paramType);
    }

    public function testItReturnsNullReturnTypeWhenNoReturnTag(): void
    {
        $parser = new PHPDocParser();
        $node = $this->createNodeWithPHPDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        );

        $returnType = $parser->parse($node)->returnType();

        self::assertNull($returnType);
    }

    public function testItReturnsLatestPrioritizedReturnTagType(): void
    {
        $parser = new PHPDocParser();
        $node = $this->createNodeWithPHPDoc(
            <<<'PHP'
                /**
                 * @example
                 * @return int
                 * @psalm-return float
                 * @psalm-return string
                 */
                PHP,
        );

        $returnType = $parser->parse($node)->returnType();

        self::assertEquals(new IdentifierTypeNode('string'), $returnType);
    }

    public function testItReturnsEmptyTemplatesWhenNoTemplateTag(): void
    {
        $parser = new PHPDocParser();
        $node = $this->createNodeWithPHPDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        );

        $templates = $parser->parse($node)->templates();

        self::assertEmpty($templates);
    }

    public function testItReturnsLatestPrioritizedTemplates(): void
    {
        $parser = new PHPDocParser();
        $node = $this->createNodeWithPHPDoc(
            <<<'PHP'
                /**
                 * @example
                 * @template T of int
                 * @template T2 of object
                 * @template T2 of mixed
                 * @psalm-template T of float
                 * @psalm-template T of string
                 */
                PHP,
        );

        $templates = $parser->parse($node)->templates();

        self::assertEquals(
            [
                $this->createTemplateTagValueNode('T', new IdentifierTypeNode('string'), Variance::INVARIANT),
                $this->createTemplateTagValueNode('T2', new IdentifierTypeNode('mixed'), Variance::INVARIANT),
            ],
            $templates,
        );
    }

    public function testItAddsVarianceAttributeToTemplates(): void
    {
        $parser = new PHPDocParser();
        $node = $this->createNodeWithPHPDoc(
            <<<'PHP'
                /**
                 * @template TInvariant
                 * @template-covariant TCovariant
                 * @template-contravariant TContravariant
                 */
                PHP,
        );

        $templates = $parser->parse($node)->templates();

        self::assertEquals(
            [
                $this->createTemplateTagValueNode('TInvariant', null, Variance::INVARIANT),
                $this->createTemplateTagValueNode('TCovariant', null, Variance::COVARIANT),
                $this->createTemplateTagValueNode('TContravariant', null, Variance::CONTRAVARIANT),
            ],
            $templates,
        );
    }

    public function testItReturnsEmptyInheritedTypesWhenNoExtendsImplementsTag(): void
    {
        $parser = new PHPDocParser();
        $node = $this->createNodeWithPHPDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        );

        $inheritedTypes = $parser->parse($node)->inheritedTypes();

        self::assertEmpty($inheritedTypes);
    }

    public function testItReturnsLatestPrioritizedImplementedTypes(): void
    {
        $parser = new PHPDocParser();
        $node = $this->createNodeWithPHPDoc(
            <<<'PHP'
                /**
                 * @example
                 * 
                 * @implements A<int>
                 * @implements B<object>
                 * @implements B<mixed>
                 * @phpstan-implements A<float>
                 * @phpstan-implements A<string>
                 * 
                 * @extends C<int>
                 * @extends D<object>
                 * @extends D<mixed>
                 * @phpstan-extends C<float>
                 * @phpstan-extends C<string>
                 */
                PHP,
        );

        $inheritedTypes = $parser->parse($node)->inheritedTypes();

        self::assertEquals(
            [
                $this->createGenericTypeNode(new IdentifierTypeNode('A'), [new IdentifierTypeNode('string')]),
                $this->createGenericTypeNode(new IdentifierTypeNode('B'), [new IdentifierTypeNode('mixed')]),
                $this->createGenericTypeNode(new IdentifierTypeNode('C'), [new IdentifierTypeNode('string')]),
                $this->createGenericTypeNode(new IdentifierTypeNode('D'), [new IdentifierTypeNode('mixed')]),
            ],
            $inheritedTypes,
        );
    }

    private function createNodeWithPHPDoc(string $phpDoc): Node
    {
        $node = $this->createStub(Node::class);
        $node->method('getDocComment')->willReturn(new Doc($phpDoc));

        return $node;
    }

    private function createTemplateTagValueNode(string $name, ?TypeNode $bound, Variance $variance): TemplateTagValueNode
    {
        $template = new TemplateTagValueNode($name, $bound, '');
        $template->setAttribute('variance', $variance);

        return $template;
    }

    /**
     * @param list<TypeNode> $genericTypes
     */
    private function createGenericTypeNode(IdentifierTypeNode $type, array $genericTypes): GenericTypeNode
    {
        return new GenericTypeNode(
            type: $type,
            genericTypes: $genericTypes,
            variances: array_fill(0, \count($genericTypes), GenericTypeNode::VARIANCE_INVARIANT),
        );
    }
}
