<?php

declare(strict_types=1);

namespace Classes\PHP82;

readonly class ReadonlyClass
{
    public string $implicitlyReadonlyProperty;
}

abstract readonly class AbstractReadonlyClass
{
    public string $implicitlyReadonlyProperty;
}

trait TraitWithConstants
{
    const C = 1;
}

final class ClassUsingTraitWithConstants
{
    use TraitWithConstants;
}

final class ClassAlteringConstantFromTrait
{
    const C = 1;

    use TraitWithConstants;
}

class ClassWith82Types
{
    public true $true;
    public false $false;
    public null $null;
}

trait TraitWithTrickyConstantExpressions
{
    const __LINE__ = __LINE__;
    const __FILE__ = __FILE__;
    const __DIR__ = __DIR__;
    const __FUNCTION__ = __FUNCTION__;
    const __CLASS__ = __CLASS__;
    const __TRAIT__ = __TRAIT__;
    const __METHOD__ = __METHOD__;
    const __NAMESPACE__ = __NAMESPACE__;
    const self = self::class;
}

trait TraitUsesTraitWithTrickyConstantExpressions
{
    use TraitWithTrickyConstantExpressions;
}

class ClassUsesTraitWithTrickyConstantExpressions
{
    use TraitWithTrickyConstantExpressions;
}
