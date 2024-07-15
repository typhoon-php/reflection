<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\ChangeDetector\FileChangeDetector;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Exception\ClassDoesNotExist;
use Typhoon\Reflection\Internal\Cache\Cache;
use Typhoon\Reflection\Internal\CodeReflector\CodeReflector;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\DeclarationId\IdMap;
use Typhoon\Reflection\Internal\ReflectionHook\ReflectionHooks;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Reflection\Locator\Locators;
use Typhoon\Reflection\Resource;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ReflectorSession implements Reflector
{
    /**
     * @var IdMap<NamedFunctionId|NamedClassId|AnonymousClassId, TypedMap|\Closure(): TypedMap>
     */
    private IdMap $buffer;

    private function __construct(
        private readonly CodeReflector $codeReflector,
        private readonly Locators $locators,
        private readonly Cache $cache,
        private readonly ReflectionHooks $hooks,
    ) {
        /** @var IdMap<NamedFunctionId|NamedClassId|AnonymousClassId, TypedMap|\Closure(): TypedMap> */
        $this->buffer = new IdMap();
    }

    public static function reflectId(
        CodeReflector $codeReflector,
        Locators $locators,
        Cache $cache,
        ReflectionHooks $hooks,
        NamedFunctionId|NamedClassId|AnonymousClassId $id,
    ): TypedMap {
        $session = new self(
            codeReflector: $codeReflector,
            locators: $locators,
            cache: $cache,
            hooks: $hooks,
        );

        try {
            $data = $session->reflect($id);
            $session->persist();
        } finally {
            $session->clear();
        }

        if ($id instanceof AnonymousClassId && isset($data[Data::AnonymousClassColumns])) {
            throw new \RuntimeException(sprintf(
                'Cannot reflect %s, because %d anonymous classes are declared at columns %s. Use TyphoonReflector::reflectAnonymousClass() with a $column argument to reflect the exact class you need',
                $id->toString(),
                \count($data[Data::AnonymousClassColumns]),
                implode(', ', $data[Data::AnonymousClassColumns]),
            ));
        }

        return $data;
    }

    /**
     * @return list<NamedFunctionId|NamedClassId|AnonymousClassId>
     */
    public static function reflectResource(
        CodeReflector $codeReflector,
        Locators $locators,
        Cache $cache,
        ReflectionHooks $hooks,
        Resource $resource,
    ): array {
        $session = new self(
            codeReflector: $codeReflector,
            locators: $locators,
            cache: $cache,
            hooks: $hooks,
        );

        try {
            $session->reflectResourceIntoBuffer($resource);
            $session->persist();

            return $session->buffer->ids();
        } finally {
            $session->clear();
        }
    }

    public function reflect(NamedFunctionId|NamedClassId|AnonymousClassId $id): TypedMap
    {
        $data = $this->buffer[$id] ?? null;

        if ($data !== null) {
            if ($data instanceof \Closure) {
                $data = $data();
                $this->buffer = $this->buffer->with($id, $data);
            }

            return $data;
        }

        $data = $this->cache->get($id);

        if ($data !== null) {
            $this->buffer = $this->buffer->with($id, $data);

            return $data;
        }

        $resource = $this->locators->locate($id);

        if ($resource === null) {
            throw new ClassDoesNotExist($id->name ?? $id->toString());
        }

        $this->reflectResourceIntoBuffer($resource);

        $data = $this->buffer[$id] ?? throw new ClassDoesNotExist($id->name ?? $id->toString());

        if ($data instanceof \Closure) {
            $data = $data();
            $this->buffer = $this->buffer->with($id, $data);
        }

        return $data;
    }

    private function reflectResourceIntoBuffer(Resource $resource): void
    {
        $reflected = $this->codeReflector
            ->reflectCode($resource->code, $resource->baseData)
            ->map(fn(TypedMap $data, NamedFunctionId|NamedClassId|AnonymousClassId $id): \Closure => function () use ($resource, $id, $data): TypedMap {
                $data = $resource->hooks->process($id, $data, $this);

                return $this->hooks->process($id, $resource->hooks->process($id, $data, $this), $this);
            });
        $this->buffer = $this->buffer->withMultiple($reflected);
        $this->addNoColumnAnonymousClassesToBuffer($reflected->ids());
    }

    private function addNoColumnAnonymousClassesToBuffer(array $reflectedIds): void
    {
        $lineToIds = [];
        $file = null;
        $changeDetector = null;

        foreach ($reflectedIds as $reflectedId) {
            if ($reflectedId instanceof AnonymousClassId) {
                $file ??= $reflectedId->file;
                $lineToIds[$reflectedId->line][] = $reflectedId;
            }
        }

        foreach ($lineToIds as $line => $ids) {
            \assert($file !== null);
            $noColumnId = Id::anonymousClass($file, $line);

            if (\count($ids) === 1) {
                $this->buffer = $this->buffer->with($noColumnId, $this->buffer[$ids[0]]);

                continue;
            }

            $changeDetector ??= FileChangeDetector::fromFile($file);

            $this->buffer = $this->buffer->with($noColumnId, (new TypedMap())
                ->set(Data::ChangeDetector, $changeDetector)
                ->set(Data::AnonymousClassColumns, array_map(
                    static fn(AnonymousClassId $id): int => $id->column ?? throw new \LogicException(),
                    $ids,
                )));
        }
    }

    private function persist(): void
    {
        $this->cache->set($this->buffer->map(static fn(TypedMap|\Closure $data): TypedMap => $data instanceof \Closure ? $data() : $data));
    }

    private function clear(): void
    {
        $this->buffer = $this->buffer->toEmpty();
    }
}
