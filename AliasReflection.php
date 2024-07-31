<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\AliasId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Misc\NonSerializable;
use Typhoon\Type\Type;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 */
final class AliasReflection
{
    use NonSerializable;

    public readonly AliasId $id;

    /**
     * This internal property is public for testing purposes.
     * It will likely be available as part of the API in the near future.
     *
     * @internal
     * @psalm-internal Typhoon
     */
    public readonly TypedMap $data;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public function __construct(AliasId $id, TypedMap $data)
    {
        $this->id = $id;
        $this->data = $data;
    }

    public function location(): ?Location
    {
        return $this->data[Data::Location];
    }

    public function type(): Type
    {
        return $this->data[Data::AliasType];
    }
}
