<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\ChangeDetector\FileChangeDetector;
use Typhoon\ChangeDetector\InMemoryChangeDetector;
use Typhoon\Reflection\Exception\FileIsNotReadable;
use Typhoon\Reflection\Internal\Hook\Hooks;

/**
 * @api
 */
final class Resource
{
    /**
     * @param non-empty-string $file
     * @param ?non-empty-string $extension
     */
    public static function fromFile(string $file, ?string $extension = null): self
    {
        $mtime = @filemtime($file);

        if ($mtime === false) {
            throw new FileIsNotReadable($file);
        }

        $code = @file_get_contents($file);

        if ($code === false) {
            throw new FileIsNotReadable($file);
        }

        return new self(
            code: $code,
            extension: $extension,
            file: $file,
            changeDetector: new FileChangeDetector(
                file: $file,
                mtime: $mtime,
                xxh3: hash(FileChangeDetector::HASHING_ALGORITHM, $code),
            ),
        );
    }

    /**
     * @param ?non-empty-string $extension
     * @param ?non-empty-string $file
     */
    public function __construct(
        public readonly string $code,
        public readonly ?string $extension = null,
        public readonly ?string $file = null,
        public readonly ChangeDetector $changeDetector = new InMemoryChangeDetector(),
        public readonly Hooks $hooks = new Hooks(),
    ) {}

    public function isInternallyDefined(): bool
    {
        return $this->extension !== null;
    }

    public function directory(): ?string
    {
        if ($this->file === null) {
            return null;
        }

        return \dirname($this->file);
    }
}
