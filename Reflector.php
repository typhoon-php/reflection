<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\ClassConstantId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\DeclarationId\PropertyId;
use Typhoon\DeclarationId\TemplateId;

/**
 * @api
 */
interface Reflector
{
    /**
     * @return (
     *     $id is ClassId ? ClassReflection :
     *     $id is ClassConstantId ? ClassConstantReflection :
     *     $id is PropertyId ? PropertyReflection :
     *     $id is MethodId ? MethodReflection :
     *     $id is ParameterId ? ParameterReflection :
     *     $id is AliasId ? AliasReflection :
     *     $id is TemplateId ? TemplateReflection :
     *     never
     * )
     */
    public function reflect(Id $id): Reflection;
}
