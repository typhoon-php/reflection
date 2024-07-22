<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Context;

use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassLike;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum NullAnnotatedTypesDriver implements AnnotatedTypesDriver
{
    case Instance;

    public function reflectAnnotatedTypeNames(FunctionLike|ClassLike $node): AnnotatedTypeNames
    {
        return new AnnotatedTypeNames();
    }
}
