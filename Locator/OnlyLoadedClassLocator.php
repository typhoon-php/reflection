<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\NamedClassId;
use function Typhoon\Reflection\Internal\class_like_exists;

/**
 * @api
 */
final class OnlyLoadedClassLocator implements NamedClassLocator
{
    public function __construct(
        private readonly NamedClassLocator $namedClassLocator,
    ) {}

    public function locate(NamedClassId $id): ?Resource
    {
        if (class_like_exists($id->name)) {
            return $this->namedClassLocator->locate($id);
        }

        return null;
    }
}
