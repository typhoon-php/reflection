<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\ChangeDetector\FileChangeDetector;
use Typhoon\Reflection\Exception\FileNotReadable;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 */
final class Resource
{
    /**
     * @param list<ReflectionHook> $hooks
     */
    public function __construct(
        public readonly string $code,
        public readonly TypedMap $data = new TypedMap(),
        public readonly array $hooks = [],
    ) {}

    /**
     * @param non-empty-string $file
     * @param list<ReflectionHook> $hooks
     */
    public static function fromFile(string $file, TypedMap $data = new TypedMap(), array $hooks = []): self
    {
        $code = @file_get_contents($file);

        if ($code === false) {
            throw new FileNotReadable($file);
        }

        return new self(
            code: $code,
            data: $data
                ->with(Data::File(), $file)
                ->with(Data::UnresolvedChangeDetectors(), [FileChangeDetector::fromFileAndContents($file, $code)]),
            hooks: $hooks,
        );
    }
}
