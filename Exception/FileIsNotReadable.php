<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exception;

/**
 * @api
 */
final class FileIsNotReadable extends \RuntimeException implements ReflectionException
{
    public function __construct(string $file, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('File "%s" does not exist or is not readable', $file), previous: $previous);
    }
}
