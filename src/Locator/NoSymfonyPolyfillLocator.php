<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;

/**
 * @api
 */
final class NoSymfonyPolyfillLocator implements NamedFunctionLocator, NamedClassLocator
{
    private const PATTERN = '/symfony/polyfill';

    private readonly Locators $locator;

    public function __construct(NamedFunctionLocator|NamedClassLocator $locator)
    {
        $this->locator = new Locators([$locator]);
    }

    public function locate(NamedFunctionId|NamedClassId $id): ?Resource
    {
        $resource = $this->locator->locate($id);

        if ($resource === null) {
            return null;
        }

        if ($resource->file !== null && str_contains($resource->file, self::PATTERN)) {
            return null;
        }

        return $resource;
    }
}
