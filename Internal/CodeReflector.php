<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\Internal\IdMap;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Context\AnnotatedTypesDriver;
use Typhoon\Reflection\Internal\Context\ContextVisitor;
use Typhoon\Reflection\Internal\PhpParser\FixNodeLocationVisitor;
use Typhoon\Reflection\Internal\PhpParser\GeneratorVisitor;
use Typhoon\Reflection\Internal\PhpParser\PhpParserChecker;
use Typhoon\Reflection\Internal\PhpParser\PhpParserReflector;
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
    public function reflectCode(TypedMap $resourceData): IdMap
    {
        $code = $resourceData[Data::Code] ?? throw new \LogicException('Code must be defined');
        $nodes = $this->phpParser->parse($code) ?? throw new \LogicException();

        /** @psalm-suppress MixedArgument, ArgumentTypeCoercion, UnusedPsalmSuppress */
        $linesFixer = method_exists($this->phpParser, 'getTokens')
            ? new FixNodeLocationVisitor($this->phpParser->getTokens())
            : FixNodeLocationVisitor::fromCode($code);

        $nameResolver = new NameResolver();
        $contextVisitor = new ContextVisitor(
            file: $resourceData[Data::File],
            code: $code,
            nameContext: $nameResolver->getNameContext(),
            annotatedTypesDriver: $this->annotatedTypesDriver,
        );
        $reflector = new PhpParserReflector(
            contextProvider: $contextVisitor,
            resourceData: $resourceData,
        );

        $traverser = new NodeTraverser();

        if (!PhpParserChecker::isVisitorLeaveReversed()) {
            $traverser->addVisitor($reflector);
        }

        $traverser->addVisitor($linesFixer);
        $traverser->addVisitor(new GeneratorVisitor());
        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($contextVisitor);

        if (PhpParserChecker::isVisitorLeaveReversed()) {
            $traverser->addVisitor($reflector);
        }

        $traverser->traverse($nodes);

        return $reflector->reflected;
    }
}
