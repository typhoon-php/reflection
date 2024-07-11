<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\ChangeDetector\FileChangeDetector;
use Typhoon\Reflection\Exception\FileNotReadable;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\Reflection\Internal\ReflectionHooks;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 */
final class Resource
{
    public readonly ReflectionHook $hook;

    /**
     * @param list<ReflectionHook> $hooks
     */
    public function __construct(
        public readonly string $code,
        public readonly TypedMap $baseData = new TypedMap(),
        array $hooks = [],
    ) {
        $this->hook = new ReflectionHooks($hooks);
    }

    /**
     * @param list<ReflectionHook> $hooks
     */
    public static function fromFile(string $file, TypedMap $baseData = new TypedMap(), array $hooks = []): self
    {
        $code = self::readFile($file);

        return new self(
            code: $code,
            baseData: $baseData
                ->set(Data::File, $file)
                ->set(Data::UnresolvedChangeDetectors, [FileChangeDetector::fromFileAndContents($file, $code)]),
            hooks: $hooks,
        );
    }

    /**
     * @psalm-assert non-empty-string $file
     * @phpstan-assert non-empty-string $file
     * @throws FileNotReadable
     */
    public static function readFile(string $file): string
    {
        $contents = @file_get_contents($file);

        if ($contents === false) {
            throw new FileNotReadable($file);
        }

        return $contents;
    }
}
