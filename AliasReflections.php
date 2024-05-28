<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\aliasId;

/**
 * @api
 * @extends Reflections<string, AliasReflection>
 */
final class AliasReflections extends Reflections
{
    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param array<non-empty-string, TypedMap> $data
     */
    public function __construct(
        private readonly ClassId|AnonymousClassId $classId,
        array $data,
    ) {
        parent::__construct($data);
    }

    protected function load(string $name, TypedMap $data): Reflection
    {
        \assert(!$this->classId instanceof AnonymousClassId);

        return new AliasReflection(aliasId($this->classId, $name), $data);
    }
}
