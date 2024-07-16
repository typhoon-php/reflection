<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
final class DontAutoloadClassLocator implements NamedClassLocator
{
    public function __construct(
        private readonly NamedClassLocator $namedClassLocator,
    ) {}

    public function locate(NamedClassId $id): ?Resource
    {
        if (class_exists($id->name, autoload: false)
            || interface_exists($id->name, autoload: false)
            || trait_exists($id->name, autoload: false)
        ) {
            return $this->namedClassLocator->locate($id);
        }

        return null;
    }
}
