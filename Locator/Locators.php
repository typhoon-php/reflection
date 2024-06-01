<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Locator;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
final class Locators implements Locator
{
    /**
     * @param iterable<Locator> $locators
     */
    public function __construct(
        private readonly iterable $locators,
    ) {}

    public function locate(ConstantId|NamedFunctionId|NamedClassId $id): ?Resource
    {
        foreach ($this->locators as $locator) {
            $resource = $locator->locate($id);

            if ($resource !== null) {
                return $resource;
            }
        }

        return null;
    }
}
