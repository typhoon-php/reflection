<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\TemplateId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Misc\NonSerializable;
use Typhoon\Type\Type;
use Typhoon\Type\Variance;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 */
final class TemplateReflection
{
    use NonSerializable;

    public readonly TemplateId $id;

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
    public function __construct(TemplateId $id, TypedMap $data)
    {
        $this->id = $id;
        $this->data = $data;
    }

    /**
     * @return non-negative-int
     */
    public function index(): int
    {
        return $this->data[Data::Index];
    }

    public function variance(): Variance
    {
        return $this->data[Data::Variance];
    }

    public function constraint(): Type
    {
        return $this->data[Data::Constraint];
    }

    public function location(): ?Location
    {
        return $this->data[Data::Location];
    }
}
