<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\Type\Type;
use function Typhoon\TypeStringifier\stringify;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class NonConvertableType extends \ReflectionException
{
    public function __construct(Type $type)
    {
        parent::__construct(sprintf('Cannot convert type %s to native ReflectionType', stringify($type)));
    }
}
