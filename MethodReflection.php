<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\MethodId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\NativeAdapter\MethodAdapter;
use Typhoon\Reflection\Internal\Visibility;
use Typhoon\Type\Type;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\parameterId;
use function Typhoon\DeclarationId\templateId;

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
     * @var TemplateReflection[]
     * @psalm-var NameMap<TemplateReflection>
     * @phpstan-var NameMap<TemplateReflection>
     */
    public readonly NameMap $templates;

    /**
     * @var ParameterReflection[]
     * @psalm-var NameMap<ParameterReflection>
     * @phpstan-var NameMap<ParameterReflection>
     */
    public readonly NameMap $parameters;

    /**
     * @var AttributeReflection[]
     * @psalm-var ListOf<AttributeReflection>
     * @phpstan-var ListOf<AttributeReflection>
     */
    public readonly ListOf $attributes;

    public function __construct(
        MethodId $id,
        TypedMap $data,
        private readonly Reflector $reflector,
    ) {
        $this->name = $id->name;
        $this->templates = (new NameMap($data[Data::Templates]))->map(
            static fn(TypedMap $data, string $name): TemplateReflection => new TemplateReflection(templateId($id, $name), $data),
        );
        $this->parameters = (new NameMap($data[Data::Parameters]))->map(
            static fn(TypedMap $data, string $name): ParameterReflection => new ParameterReflection(parameterId($id, $name), $data, $reflector),
        );
        $this->attributes = (new ListOf($data[Data::Attributes]))->map(
            static fn(TypedMap $data): AttributeReflection => new AttributeReflection($id, $data, $reflector),
        );

        parent::__construct($id, $data);
    }

    /**
     * @return ?non-empty-string
     */
    public function phpDoc(): ?string
    {
        return $this->data[Data::PhpDoc];
    }

    public function class(): ClassReflection
    {
        return $this->reflector->reflect($this->id->class);
    }

    /**
     * @return ?non-empty-string
     */
    public function file(): ?string
    {
        if ($this->data[Data::InternallyDefined]) {
            return null;
        }

        return $this->declaringClass()->file();
    }

    public function isInternallyDefined(): bool
    {
        return $this->data[Data::InternallyDefined] || $this->declaringClass()->isInternallyDefined();
    }

    public function isAbstract(): bool
    {
        return $this->data[Data::Abstract];
    }

    public function isFinal(Kind $kind = Kind::Resolved): bool
    {
        return match ($kind) {
            Kind::Native => $this->data[Data::NativeFinal],
            Kind::Annotated => $this->data[Data::AnnotatedFinal],
            Kind::Resolved => $this->data[Data::NativeFinal] || $this->data[Data::AnnotatedFinal],
        };
    }

    public function isGenerator(): bool
    {
        return $this->data[Data::Generator];
    }

    public function isPrivate(): bool
    {
        return $this->data[Data::Visibility] === Visibility::Private;
    }

    public function isProtected(): bool
    {
        return $this->data[Data::Visibility] === Visibility::Protected;
    }

    public function isPublic(): bool
    {
        $visibility = $this->data[Data::Visibility];

        return $visibility === null || $visibility === Visibility::Public;
    }

    public function isStatic(): bool
    {
        return $this->data[Data::Static];
    }

    public function isVariadic(): bool
    {
        $lastParameterName = array_key_last($this->parameters->names());

        return $lastParameterName !== null && $this->parameters[$lastParameterName]->isVariadic();
    }

    public function returnsReference(): bool
    {
        return $this->data[Data::ByReference];
    }

    /**
     * @return ($kind is Kind::Resolved ? Type : ?Type)
     */
    public function returnType(Kind $kind = Kind::Resolved): ?Type
    {
        return $this->data[Data::Type]->byKind($kind);
    }

    public function throwsType(): ?Type
    {
        return $this->data[Data::ThrowsType];
    }

    public function toNative(): \ReflectionMethod
    {
        return new MethodAdapter($this, $this->reflector);
    }

    private function declaringClass(): ClassReflection
    {
        return $this->reflector->reflect($this->data[Data::DeclaringClassId]);
    }
}
