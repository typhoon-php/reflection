<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exception;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 */
final class DeclarationNotFoundInResource extends \LogicException implements ReflectionException
{
    public function __construct(
        TypedMap $data,
        public readonly ConstantId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id,
    ) {
        $file = $data[Data::File];

        parent::__construct(\sprintf(
            '%s not found in %s',
            ucfirst($id->describe()),
            $file ?? substr($data[Data::Code], 0, 50),
        ));
    }
}
