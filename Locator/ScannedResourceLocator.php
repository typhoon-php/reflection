<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;

/**
 * @api
 */
final class ScannedResourceLocator implements ConstantLocator, NamedFunctionLocator, NamedClassLocator, AnonymousLocator
{
    /**
     * @var array<non-empty-string, true>
     */
    private readonly array $idsMap;

    /**
     * @param list<Id> $ids
     */
    public function __construct(
        array $ids,
        private readonly Resource $resource,
    ) {
        $idsMap = [];

        foreach ($ids as $id) {
            $idsMap[$id->encode()] = true;
        }

        $this->idsMap = $idsMap;
    }

    public function locate(ConstantId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id): ?Resource
    {
        return isset($this->idsMap[$id->encode()]) ? $this->resource : null;
    }
}
