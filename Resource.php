<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\ChangeDetector\FileChangeDetector;
use Typhoon\Reflection\Exception\FileNotReadable;
use Typhoon\Reflection\Internal\ClassReflectionHook;
use Typhoon\Reflection\Internal\ConstantReflectionHook;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\FunctionReflectionHook;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @api
 */
final class Resource
{
    /**
     * @param list<ConstantReflectionHook|FunctionReflectionHook|ClassReflectionHook> $hooks
     */
    public function __construct(
        public readonly string $code,
        public readonly TypedMap $baseData = new TypedMap(),
        public readonly array $hooks = [],
    ) {}

    /**
     * @param list<ConstantReflectionHook|FunctionReflectionHook|ClassReflectionHook> $hooks
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
