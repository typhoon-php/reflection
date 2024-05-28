<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\DeclarationId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 * @template-covariant TId of DeclarationId
 */
abstract class Reflection
{
    /**
     * @param TId $id
     */
    public function __construct(
        public readonly DeclarationId $id,
        public readonly TypedMap $data,
        protected readonly Reflector $reflector,
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
