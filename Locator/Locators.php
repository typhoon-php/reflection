<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
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

    public function locate(ClassId|FunctionId $id): ?Resource
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
