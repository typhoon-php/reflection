<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\AliasId;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Type\Type;

/**
 * @api
 * @extends Reflection<AliasId>
 */
final class AliasReflection extends Reflection
{
    /**
     * @var non-empty-string
     */
    public readonly string $name;

    public function __construct(AliasId $id, TypedMap $data)
    {
        $this->name = $id->name;

        parent::__construct($id, $data);
    }

    public function type(): Type
    {
        return $this->data[Data::AliasType];
    }
}
