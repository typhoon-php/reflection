<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\ChangeDetector\FileChangeDetector;
use Typhoon\DeclarationId\AnonymousClassId;
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
     * @param non-empty-string $file
     * @param list<ReflectionHook> $hooks
     */
    private function __construct(
        public readonly string $file,
        public readonly string $code,
        public readonly TypedMap $baseData,
        public readonly array $hooks = [],
    ) {}

    public static function fromAnonymousClassId(AnonymousClassId $id): self
    {
        return new self(
            file: $id->file,
            code: self::readFile($id->file),
            baseData: (new TypedMap())->set(Data::File, $id->file),
        );
    }

    public static function fromResource(Resource $resource): self
    {
        $code = self::readFile($resource->file);
        $baseData = $resource->baseData;

        if (!$baseData[Data::WrittenInC]) {
            $baseData = $baseData->set(Data::File, $resource->file);
        }

        if (!isset($baseData[Data::UnresolvedChangeDetectors])) {
            $baseData = $baseData->set(Data::UnresolvedChangeDetectors, [
                FileChangeDetector::fromFileAndContents($resource->file, $code),
            ]);
        }

        return new self(
            file: $resource->file,
            code: $code,
            baseData: $baseData,
            hooks: $resource->hooks,
        );
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
