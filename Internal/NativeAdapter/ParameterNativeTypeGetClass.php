<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\ParameterReflection;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Type\Type;
use Typhoon\Type\Visitor\DefaultTypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\NativeAdapter
 * @extends DefaultTypeVisitor<?ClassReflection>
 */
final class ParameterNativeTypeGetClass extends DefaultTypeVisitor
{
    public function __construct(
        private readonly ParameterReflection $reflection,
        private readonly TyphoonReflector $reflector,
    ) {}

    public function namedObject(Type $type, NamedClassId $classId, array $typeArguments): mixed
    {
        return $this->reflector->reflect($classId);
    }

    public function self(Type $type, array $typeArguments, null|NamedClassId|AnonymousClassId $resolvedClassId): mixed
    {
        return $this->reflection->class();
    }

    public function parent(Type $type, array $typeArguments, ?NamedClassId $resolvedClassId): mixed
    {
        return $this->reflection->class()?->parent();
    }

    public function union(Type $type, array $ofTypes): mixed
    {
        $isNull = new ParameterNativeTypeIsNull();
        $class = null;

        foreach ($ofTypes as $ofType) {
            if ($ofType->accept($isNull)) {
                continue;
            }

            $ofTypeClass = $ofType->accept($this);

            if ($ofTypeClass !== null) {
                if ($class !== null) {
                    return null;
                }

                $class = $ofTypeClass;
            }
        }

        return $class;
    }

    protected function default(Type $type): mixed
    {
        return null;
    }
}
