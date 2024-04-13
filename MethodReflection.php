<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\MethodId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\NativeAdapter\MethodAdapter;
use Typhoon\Reflection\Internal\Visibility;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\parameterId;

/**
 * @api
 * @extends Reflection<MethodId>
 */
final class MethodReflection extends Reflection
{
    /**
     * @var non-empty-string
     */
    public readonly string $name;

    /**
     * @var ?array<non-empty-string, ParameterReflection>
     */
    private ?array $parameters = null;

    public function __construct(MethodId $id, TypedMap $data, Reflector $reflector)
    {
        $this->name = $id->name;

        parent::__construct($id, $data, $reflector);
    }

    public function class(): ClassReflection
    {
        return $this->reflector->reflect($this->id->class);
    }

    public function declaringClass(): ClassReflection
    {
        return $this->reflector->reflect($this->declarationId()->class);
    }

    /**
     * @return ?non-empty-string
     */
    public function file(): ?string
    {
        if ($this->data[Data::WrittenInC()] ?? false) {
            return null;
        }

        return $this->declaringClass()->file();
    }

    public function isWrittenInC(): bool
    {
        return ($this->data[Data::WrittenInC()] ?? false) || $this->declaringClass()->isWrittenInC();
    }

    public function isAbstract(): bool
    {
        return $this->data[Data::Abstract()] ?? false;
    }

    public function isFinal(Kind $kind = Kind::Resolved): bool
    {
        return match ($kind) {
            Kind::Native => $this->data[Data::NativeFinal()] ?? false,
            Kind::Annotated => $this->data[Data::AnnotatedFinal()] ?? false,
            Kind::Resolved => $this->data[Data::NativeFinal()] ?? $this->data[Data::AnnotatedFinal()] ?? false,
        };
    }

    public function isGenerator(): bool
    {
        return $this->data[Data::Generator()] ?? false;
    }

    public function isPrivate(): bool
    {
        return $this->data[Data::Visibility()] === Visibility::Private;
    }

    public function isProtected(): bool
    {
        return $this->data[Data::Visibility()] === Visibility::Protected;
    }

    public function isPublic(): bool
    {
        $visibility = $this->data[Data::Visibility()];

        return $visibility === null || $visibility === Visibility::Public;
    }

    public function isStatic(): bool
    {
        return $this->data[Data::Static()];
    }

    public function isVariadic(): bool
    {
        $parameters = $this->parameters();
        $lastParameterName = array_key_last($parameters);

        return $lastParameterName !== null && $parameters[$lastParameterName]->isVariadic();
    }

    /**
     * @return non-negative-int
     */
    public function numberOfRequiredParameters(): int
    {
        $parameter = null;

        foreach ($this->parameters() as $parameter) {
            if ($parameter->isOptional()) {
                return $parameter->index;
            }
        }

        if ($parameter === null) {
            return 0;
        }

        return $parameter->index + 1;
    }

    // public function prototype(): ?self
    // {
    // }

    public function returnsReference(): bool
    {
        return $this->data[Data::ByReference()] ?? false;
    }

    /**
     * @return ($kind is Kind::Resolved ? Type : ?Type)
     */
    public function returnType(Kind $kind = Kind::Resolved): ?Type
    {
        return match ($kind) {
            Kind::Native => $this->data[Data::NativeType()] ?? null,
            Kind::Annotated => $this->data[Data::AnnotatedType()] ?? null,
            Kind::Resolved => $this->data[Data::ResolvedType()]
                ?? $this->data[Data::AnnotatedType()]
                ?? $this->data[Data::NativeType()]
                ?? types::mixed,
        };
    }

    public function parameter(int|string $indexOrName): ?ParameterReflection
    {
        if (\is_int($indexOrName)) {
            return array_values($this->parameters())[$indexOrName] ?? null;
        }

        return $this->parameters()[$indexOrName] ?? null;
    }

    /**
     * @return array<non-empty-string, ParameterReflection>
     */
    public function parameters(): array
    {
        if ($this->parameters !== null) {
            return $this->parameters;
        }

        $this->parameters = [];

        foreach ($this->data[Data::Parameters()] as $name => $data) {
            $this->parameters[$name] = new ParameterReflection(
                id: parameterId($this->id, $name),
                data: $data,
                reflector: $this->reflector,
            );
        }

        return $this->parameters;
    }

    public function toNative(): \ReflectionMethod
    {
        return new MethodAdapter($this);
    }
}
