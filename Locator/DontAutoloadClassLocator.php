<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\NamedClassId;
use function Typhoon\Reflection\Internal\classLikeExists;

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
        if (classLikeExists($id->name)) {
            return $this->namedClassLocator->locate($id);
        }

        return null;
    }
}
