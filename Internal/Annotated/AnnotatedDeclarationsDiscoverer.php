<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Annotated;

use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassLike;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface AnnotatedDeclarationsDiscoverer
{
    public function discoverAnnotatedDeclarations(FunctionLike|ClassLike $node): AnnotatedDeclarations;
}
