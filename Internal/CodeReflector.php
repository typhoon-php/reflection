<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Expression\ExpressionCompilerVisitor;
use Typhoon\Reflection\Internal\PhpParserReflector\FixNodeStartLineVisitor;
use Typhoon\Reflection\Internal\PhpParserReflector\PhpParserReflector;
use Typhoon\Reflection\Internal\ReflectPhpDocTypes\ReflectPhpDocTypes;
use Typhoon\Reflection\Internal\TypeContext\TypeContextVisitor;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class CodeReflector
{
    public function __construct(
        private readonly Parser $phpParser,
    ) {}

    /**
     * @param ?non-empty-string $file
     * @return IdMap<NamedClassId|AnonymousClassId, TypedMap>
     */
    public function reflectCode(string $code, ?string $file = null): IdMap
    {
        $nodes = $this->phpParser->parse($code) ?? throw new \LogicException();

        $traverser = new NodeTraverser();
        $nameResolver = new NameResolver();
        $typeContextVisitor = new TypeContextVisitor(
            nameContext: $nameResolver->getNameContext(),
            reader: new ReflectPhpDocTypes(),
            code: $code,
            file: $file,
        );
        $expressionCompilerVisitor = new ExpressionCompilerVisitor($file ?? '');
        $reflector = new PhpParserReflector($typeContextVisitor, $expressionCompilerVisitor);
        $traverser->addVisitor(new FixNodeStartLineVisitor($this->phpParser->getTokens()));
        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($typeContextVisitor);
        $traverser->addVisitor($expressionCompilerVisitor);
        $traverser->addVisitor($reflector);
        $traverser->traverse($nodes);

        return $reflector->reflected;
    }
}
