<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\Id;
use Typhoon\Reflection\Internal\Data;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 * @template-covariant TId of Id
 */
abstract class Reflection
{
    /**
     * This property is public but internal for testing purposes.
     * It will likely be available as part of the API in the near future.
     *
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public readonly TypedMap $data;

    /**
     * @param TId $id
     */
    public function __construct(
        public readonly Id $id,
        TypedMap $data,
    ) {
        $this->data = $data;
    }

    /**
     * @return ?positive-int
     */
    final public function startLine(): ?int
    {
        return $this->data[Data::StartLine];
    }

    /**
     * @return ?positive-int
     */
    final public function endLine(): ?int
    {
        return $this->data[Data::EndLine];
    }
}
