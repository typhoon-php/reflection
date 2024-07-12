<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpDoc;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser as PHPStanPhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PhpDocParser
{
    public function __construct(
        private readonly TagPrioritizer $tagPrioritizer = new PrefixBasedTagPrioritizer(),
        private readonly PHPStanPhpDocParser $parser = new PHPStanPhpDocParser(
            typeParser: new TypeParser(new ConstExprParser()),
            constantExprParser: new ConstExprParser(),
        ),
        private readonly Lexer $lexer = new Lexer(),
    ) {}

    public function parse(string $comment): PhpDoc
    {
        $tokens = $this->lexer->tokenize($comment);
        $phpDoc = $this->parser->parse(new TokenIterator($tokens));

        return new PhpDoc($this->tagPrioritizer, $phpDoc->getTags());
    }
}
