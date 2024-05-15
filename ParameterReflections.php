<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\FunctionId;
use Typhoon\DeclarationId\MethodId;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\parameterId;

/**
 * @api
 * @extends Reflections<int|string, ParameterReflection>
 */
final class ParameterReflections extends Reflections
{
    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param array<non-empty-string, TypedMap> $data
     */
    public function __construct(
        private readonly FunctionId|MethodId $functionId,
        array $data,
        private readonly Reflector $reflector,
    ) {
        parent::__construct($data);
    }

    /**
     * @return non-negative-int
     */
    public function countRequired(): int
    {
        $parameter = null;

        foreach ($this as $parameter) {
            if ($parameter->isOptional()) {
                return $parameter->index;
            }
        }

        if ($parameter === null) {
            return 0;
        }

        return $parameter->index + 1;
    }

    /**
     * @return array<non-empty-string, \ReflectionParameter>
     */
    public function toNative(): array
    {
        return $this->map(
            static fn(ParameterReflection $parameter): \ReflectionParameter => $parameter->toNative(),
        );
    }

    protected function load(string $name, TypedMap $data): Reflection
    {
        return new ParameterReflection(parameterId($this->functionId, $name), $data, $this->reflector);
    }
}
