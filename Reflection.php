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
     * @param TId $id
     */
    public function __construct(
        public readonly Id $id,
        public readonly TypedMap $data,
    ) {}

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
