<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\ChangeDetector\FileChangeDetector;
use Typhoon\Reflection\Exception\FileIsNotReadable;
use Typhoon\Reflection\Internal\ClassHook;
use Typhoon\Reflection\Internal\ConstantHook;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\FunctionHook;
use Typhoon\Reflection\Internal\Hooks;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 */
final class Resource
{
    private function __construct(
        public readonly TypedMap $data,
        public readonly Hooks $hooks,
    ) {}

    /**
     * @param iterable<ConstantHook|FunctionHook|ClassHook> $hooks
     */
    public static function fromCode(string $code, TypedMap $data = new TypedMap(), iterable $hooks = []): self
    {
        return new self(
            data: $data->with(Data::Code, $code),
            hooks: new Hooks($hooks),
        );
    }

    /**
     * @param non-empty-string $file
     * @param iterable<ConstantHook|FunctionHook|ClassHook> $hooks
     */
    public static function fromFile(string $file, TypedMap $data = new TypedMap(), iterable $hooks = []): self
    {
        $handle = @fopen($file, 'r');

        if ($handle === false) {
            throw new FileIsNotReadable($file);
        }

        if (!@flock($handle, LOCK_SH)) {
            throw new \RuntimeException('Failed to acquire shared lock on file ' . $file);
        }

        try {
            $mtime = @filemtime($file);

            if ($mtime === false) {
                throw new FileIsNotReadable($file);
            }

            $code = @file_get_contents($file);

            if ($code === false) {
                throw new FileIsNotReadable($file);
            }

            return new self(
                data: $data
                    ->with(Data::Code, $code)
                    ->with(Data::File, $file)
                    ->with(Data::ChangeDetector, new FileChangeDetector($file, $mtime, md5($code))),
                hooks: new Hooks($hooks),
            );
        } finally {
            @fclose($handle);
        }
    }
}
