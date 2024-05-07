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
        public readonly array $hooks,
    ) {}

    public static function resolve(Resource $resource): self
    {
        $baseData = $resource->baseData;

        \assert($resource->file !== '');
        $code = @file_get_contents($resource->file);

        if ($code === false) {
            throw new FileNotReadable($resource->file);
        }

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
}
