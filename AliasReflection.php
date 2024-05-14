<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\AliasId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Type\Type;
use Typhoon\TypedMap\TypedMap;

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

    public function __construct(AliasId $id, TypedMap $data, Reflector $reflector)
    {
        $this->name = $id->name;

        parent::__construct($id, $data, $reflector);
    }

    public function type(): Type
    {
        return $this->data[Data::AliasType];
    }
}
