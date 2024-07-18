<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exception;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Locator\AnonymousLocator;
use Typhoon\Reflection\Locator\ConstantLocator;
use Typhoon\Reflection\Locator\NamedClassLocator;
use Typhoon\Reflection\Locator\NamedFunctionLocator;

/**
 * @api
 */
final class LocatorFailed extends \RuntimeException implements ReflectionException
{
    /**
     * @param class-string<ConstantLocator|NamedFunctionLocator|NamedClassLocator|AnonymousLocator> $locatorClass
     */
    public function __construct(
        string $locatorClass,
        public readonly ConstantId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $declarationId,
        \Throwable $previous,
    ) {
        parent::__construct(sprintf('Locator %s failed to locate %s', $locatorClass, $declarationId->describe()), previous: $previous);
    }
}
