<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\AliasId;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Type\Type;

/**
 * @api
 */
final class AliasReflection
{
    /**
     * @var non-empty-string
     */
    public readonly string $name;

    /**
     * This internal property is public for testing purposes.
     * It will likely be available as part of the API in the near future.
     *
     * @internal
     * @psalm-internal Typhoon
     */
    public readonly TypedMap $data;

    public function __construct(
        public readonly AliasId $id,
        TypedMap $data,
    ) {
        $this->name = $id->name;
        $this->data = $data;
    }

    /**
     * @return ?positive-int
     */
    public function startLine(): ?int
    {
        return $this->data[Data::StartLine];
    }

    /**
     * @return ?positive-int
     */
    public function endLine(): ?int
    {
        return $this->data[Data::EndLine];
    }

    public function type(): Type
    {
        return $this->data[Data::AliasType];
    }
}
