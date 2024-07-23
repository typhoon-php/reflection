<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpDoc;

use PHPStan\PhpDocParser\Ast;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\PhpDoc
 * @todo issue
 */
final class AlwaysTrimmingConstExprParser extends ConstExprParser
{
    public function parse(TokenIterator $tokens, bool $trimStrings = false): Ast\ConstExpr\ConstExprNode
    {
        return parent::parse($tokens, true);
    }
}
