<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\ChangeDetector\FileChangeDetector;
use Typhoon\Reflection\Exception\FileIsNotReadable;
use Typhoon\Reflection\Internal\ClassHook;
use Typhoon\Reflection\Internal\ConstantHook;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\FunctionHook;
use Typhoon\Reflection\Internal\Hooks;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @api
 */
final class Resource
{
    public readonly TypedMap $baseData;

    public readonly Hooks $hooks;

    /**
     * @param list<ConstantHook|FunctionHook|ClassHook> $hooks
     */
    public function __construct(
        // TODO remove
        string $code,
        TypedMap $baseData = new TypedMap(),
        array $hooks = [],
    ) {
        $this->baseData = $baseData->with(Data::Code, $code);
        $this->hooks = new Hooks($hooks);
    }

    /**
     * @param list<ConstantHook|FunctionHook|ClassHook> $hooks
     */
    public static function fromFile(string $file, TypedMap $baseData = new TypedMap(), array $hooks = []): self
    {
        $code = self::readFile($file);

        return new self(
            code: $code,
            baseData: $baseData
                ->with(Data::File, $file)
                ->with(Data::UnresolvedChangeDetectors, [FileChangeDetector::fromFileAndContents($file, $code)]),
            hooks: $hooks,
        );
    }

    /**
     * @psalm-assert non-empty-string $file
     * @phpstan-assert non-empty-string $file
     * @throws FileIsNotReadable
     */
    public static function readFile(string $file): string
    {
        $contents = @file_get_contents($file);

        if ($contents === false) {
            throw new FileIsNotReadable($file);
        }

        return $contents;
    }
}
