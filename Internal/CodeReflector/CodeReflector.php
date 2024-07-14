<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CodeReflector;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\ConstantExpression\ConstantConstantExpressionCompilerVisitor;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\DeclarationId\IdMap;
use Typhoon\Reflection\Internal\PhpParser\FixNodeStartLineVisitor;
use Typhoon\Reflection\Internal\PhpParser\PhpParserChecker;
use Typhoon\Reflection\Internal\TypeContext\AnnotatedTypesDriver;
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
        private readonly AnnotatedTypesDriver $annotatedTypesDriver,
    ) {}

    /**
     * @return IdMap<NamedFunctionId|NamedClassId|AnonymousClassId, TypedMap>
     */
    public function reflectCode(string $code, TypedMap $baseData = new TypedMap()): IdMap
    {
        $file = $baseData[Data::File];
        $nodes = $this->phpParser->parse($code) ?? throw new \LogicException();

        /** @psalm-suppress MixedArgument, UnusedPsalmSuppress */
        $linesFixer = method_exists($this->phpParser, 'getTokens')
            ? new FixNodeStartLineVisitor($this->phpParser->getTokens())
            : FixNodeStartLineVisitor::fromCode($code);

        $nameResolver = new NameResolver();
        $typeContextVisitor = new TypeContextVisitor(
            nameContext: $nameResolver->getNameContext(),
            annotatedTypesDriver: $this->annotatedTypesDriver,
            code: $code,
            file: $file,
        );
        $expressionCompilerVisitor = new ConstantConstantExpressionCompilerVisitor($file);
        $reflector = new PhpParserReflector($typeContextVisitor, $expressionCompilerVisitor, $baseData);

        $traverser = new NodeTraverser();

        if (!PhpParserChecker::isVisitorLeaveReversed()) {
            $traverser->addVisitor($reflector);
        }

        $traverser->addVisitor($linesFixer);
        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($typeContextVisitor);
        $traverser->addVisitor($expressionCompilerVisitor);

        if (PhpParserChecker::isVisitorLeaveReversed()) {
            $traverser->addVisitor($reflector);
        }

        $traverser->traverse($nodes);

        return $reflector->reflected;
    }
}
