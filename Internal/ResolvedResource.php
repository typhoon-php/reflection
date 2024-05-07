<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\ChangeDetector\FileChangeDetector;
use Typhoon\Reflection\Exception\FileNotReadable;
use Typhoon\Reflection\Resource;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ResolvedResource
{
    /**
     * @param list<ReflectionHook> $hooks
     */
    private function __construct(
        public readonly string $code,
        public readonly TypedMap $baseData,
        public readonly array $hooks = [],
    ) {}

    public static function fromAnonymousClassName(string $name): self
    {
        $matched = preg_match('/anonymous\x00(.+):(\d+)/', $name, $matches) === 1;
        \assert($matched, sprintf('Invalid anonymous class name "%s"', $name));

        $code = self::readFile($matches[1]);
        $line = (int) $matches[2];
        \assert($line > 0, 'Anonymous class line must be a positive int');

        return new self(
            code: $code,
            baseData: (new TypedMap())
                ->with(Data::StartLine(), $line)
                ->with(Data::File(), $matches[1]),
        );
    }

    public static function fromResource(Resource $resource): self
    {
        $code = self::readFile($resource->file);
        $baseData = $resource->baseData;

        if (!($baseData[Data::WrittenInC()] ?? false)) {
            $baseData = $baseData->with(Data::File(), $resource->file);
        }

        if (!isset($baseData[Data::UnresolvedChangeDetectors()])) {
            $baseData = $baseData->with(Data::UnresolvedChangeDetectors(), [
                FileChangeDetector::fromFileAndContents($resource->file, $code),
            ]);
        }

        return new self($code, $baseData, $resource->hooks);
    }

    /**
     * @psalm-assert non-empty-string $file
     */
    private static function readFile(string $file): string
    {
        $code = @file_get_contents($file);

        if ($code === false) {
            throw new FileNotReadable($file);
        }

        return $code;
    }
}
