<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Annotated;

use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassLike;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum NullAnnotatedDeclarationsDiscoverer implements AnnotatedDeclarationsDiscoverer
{
    case Instance;

    public function discoverAnnotatedDeclarations(FunctionLike|ClassLike $node): AnnotatedDeclarations
    {
        return new AnnotatedDeclarations();
    }
}
