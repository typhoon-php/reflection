<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\Internal\IdMap;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;

/**
 * @api
 */
final class DeterministicLocator implements ConstantLocator, NamedFunctionLocator, NamedClassLocator, AnonymousLocator
{
    /**
     * @param IdMap<ConstantId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId, \Typhoon\Reflection\Locator\Resource> $resources
     */
    public function __construct(
        private IdMap $resources,
    ) {}

    public function locate(ConstantId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id): ?Resource
    {
        return $this->resources[$id] ?? null;
    }
}
