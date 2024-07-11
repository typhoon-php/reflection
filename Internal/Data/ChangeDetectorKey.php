<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\Reflection\Internal\TypedMap\Key;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements Key<ChangeDetector>
 */
enum ChangeDetectorKey implements Key
{
    case Key;
}
