<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Locators;

/**
 * @api
 */
final class NoSymfonyPolyfillLocator implements NamedFunctionLocator, NamedClassLocator
{
    private const NEEDLE = '/symfony/polyfill';

    private readonly Locators $locator;

    public function __construct(
        NamedFunctionLocator|NamedClassLocator $locator,
    ) {
        $this->locator = new Locators([$locator]);
    }

    public function locate(NamedFunctionId|NamedClassId $id): ?Resource
    {
        $resource = $this->locator->locate($id);

        if ($resource === null) {
            return null;
        }

        $file = $resource->data[Data::File];

        if ($file !== null && str_contains($file, self::NEEDLE)) {
            return null;
        }

        return $resource;
    }
}
