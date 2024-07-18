<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exception;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;

/**
 * This exception is thrown when configured locators do not return a resource or throw an exception.
 *
 * @api
 */
final class FailedToLocate extends \RuntimeException implements ReflectionException
{
    public function __construct(
        public readonly ConstantId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(sprintf('Failed to locate %s', $id->describe()), previous: $previous);
    }
}
