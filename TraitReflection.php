<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 * @readonly
 * @template-covariant TObject of object
 * @extends ClassLikeReflection<TObject, NamedClassId>
 */
final class TraitReflection extends ClassLikeReflection
{
    /**
     * @var class-string<TObject>
     */
    public readonly string $name;

    /**
     * @var ?NameMap<AliasReflection>
     */
    private ?NameMap $aliases = null;

    public function __construct(NamedClassId $id, TypedMap $data, Reflector $reflector)
    {
        /** @var class-string<TObject> */
        $this->name = $id->name;
        parent::__construct($id, $data, $reflector);
    }

    /**
     * @return AliasReflection[]
     * @psalm-return NameMap<AliasReflection>
     * @phpstan-return NameMap<AliasReflection>
     */
    public function aliases(): NameMap
    {
        return $this->aliases ??= (new NameMap($this->data[Data::Aliases]))->map(
            fn(TypedMap $data, string $name): AliasReflection => new AliasReflection(Id::alias($this->id, $name), $data),
        );
    }
}
