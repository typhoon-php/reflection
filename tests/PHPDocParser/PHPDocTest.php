<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\PHPDocParser;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PHPDoc::class)]
final class PHPDocTest extends TestCase
{
    public function testItReturnsNullTypeIfNoVarTag(): void
    {
        $phpDoc = $this->parse(
            <<<'PHP'
                /**
                 * @param string $a
                 */
                PHP,
        );

        $varType = $phpDoc->varType();

        self::assertNull($varType);
    }

    public function testItReturnsFirstVarTagType(): void
    {
        $phpDoc = $this->parse(
            <<<'PHP'
                /**
                 * @param float $a
                 * @var string
                 * @var int
                 */
                PHP,
        );

        $varType = $phpDoc->varType();

        self::assertEquals(new IdentifierTypeNode('string'), $varType);
    }

    public function testItReturnsNullTypeIfNoParamTag(): void
    {
        $phpDoc = $this->parse(
            <<<'PHP'
                /**
                 * @var string
                 */
                PHP,
        );

        $paramType = $phpDoc->paramType('a');

        self::assertNull($paramType);
    }

    public function testItReturnsNullTypeIfParamTagIsForDifferentVariable(): void
    {
        $phpDoc = $this->parse(
            <<<'PHP'
                /**
                 * @param string $b
                 */
                PHP,
        );

        $paramType = $phpDoc->paramType('a');

        self::assertNull($paramType);
    }

    public function testItReturnsFirstParamTagType(): void
    {
        $phpDoc = $this->parse(
            <<<'PHP'
                /**
                 * @var float
                 * @param string $a
                 * @param int $a
                 */
                PHP,
        );

        $paramType = $phpDoc->paramType('a');

        self::assertEquals(new IdentifierTypeNode('string'), $paramType);
    }

    public function testItReturnsNullTypeIfNoReturnTag(): void
    {
        $phpDoc = $this->parse(
            <<<'PHP'
                /**
                 * @var string
                 */
                PHP,
        );

        $returnType = $phpDoc->returnType();

        self::assertNull($returnType);
    }

    public function testItReturnsFirstReturnTagType(): void
    {
        $phpDoc = $this->parse(
            <<<'PHP'
                /**
                 * @var float
                 * @return string
                 * @return int
                 */
                PHP,
        );

        $returnType = $phpDoc->returnType();

        self::assertEquals(new IdentifierTypeNode('string'), $returnType);
    }

    public function testItReturnsEmptyArrayIfNoTemplateTags(): void
    {
        $phpDoc = $this->parse(
            <<<'PHP'
                /**
                 * @var float
                 */
                PHP,
        );

        $templateTags = $phpDoc->templateTags();

        self::assertSame([], $templateTags);
    }

    public function testItReturnsFirstTemplateNames(): void
    {
        $phpDoc = $this->parse(
            <<<'PHP'
                /**
                 * @template T1 of string
                 * @var float
                 * @template-covariant T2
                 * @template T1
                 */
                PHP,
        );

        $templateNames = $phpDoc->templateTags();

        self::assertEquals(
            [
                new PhpDocTagNode('@template', new TemplateTagValueNode('T1', new IdentifierTypeNode('string'), '')),
                new PhpDocTagNode('@template-covariant', new TemplateTagValueNode('T2', null, '')),
            ],
            $templateNames,
        );
    }

    public function testItReturnsEmptyArrayIfNoInheritedTypes(): void
    {
        $phpDoc = $this->parse(
            <<<'PHP'
                /**
                 * @var float
                 */
                PHP,
        );

        $inheritedTypes = $phpDoc->inheritedTypes();

        self::assertSame([], $inheritedTypes);
    }

    public function testItReturnsFirstInheritedTypes(): void
    {
        $phpDoc = $this->parse(
            <<<'PHP'
                /**
                 * @var float 
                 * @implements a<string>
                 * @implements a<float>
                 * @extends b<bool>
                 * @extends b<int>
                 */
                PHP,
        );

        $inheritedTypes = $phpDoc->inheritedTypes();

        self::assertEquals(
            [
                new GenericTypeNode(new IdentifierTypeNode('a'), [new IdentifierTypeNode('string')], ['invariant']),
                new GenericTypeNode(new IdentifierTypeNode('b'), [new IdentifierTypeNode('bool')], ['invariant']),
            ],
            $inheritedTypes,
        );
    }

    private function parse(string $phpDoc): PHPDoc
    {
        $phpDocParser = new PHPDocParser();
        $node = $this->createStub(Node::class);
        $node->method('getDocComment')->willReturn(new Doc($phpDoc));

        return $phpDocParser->parse($node);
    }
}
