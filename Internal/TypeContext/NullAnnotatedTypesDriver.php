<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\TypeContext;

use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassLike;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class NullAnnotatedTypesDriver implements AnnotatedTypesDriver
{
    public function reflectTypeDeclarations(ClassLike|FunctionLike $node): TypeDeclarations
    {
        return new TypeDeclarations();
    }
}
