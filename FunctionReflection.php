<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\NativeAdapter\FunctionAdapter;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Type\Type;

/**
 * @api
 * @template-covariant TName of ?callable-string
 */
final class FunctionReflection
{
    public readonly AnonymousFunctionId|NamedFunctionId $id;

    /**
     * @var TName
     */
    public readonly ?string $name;

    /**
     * This internal property is public for testing purposes.
     * It will likely be available as part of the API in the near future.
     *
     * @internal
     * @psalm-internal Typhoon
     */
    public readonly TypedMap $data;

    /**
     * @var ?NameMap<TemplateReflection>
     */
    private ?NameMap $templates = null;

    /**
     * @var ?ListOf<AttributeReflection>
     */
    private ?ListOf $attributes = null;

    /**
     * @var ?NameMap<ParameterReflection>
     */
    private ?NameMap $parameters;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public function __construct(
        NamedFunctionId|AnonymousFunctionId $id,
        TypedMap $data,
        private readonly TyphoonReflector $reflector,
    ) {
        $this->id = $id;
        /** @var TName */
        $this->name = $id instanceof NamedFunctionId ? $id->name : null;
        $this->data = $data;
    }

    /**
     * @return TemplateReflection[]
     * @psalm-return NameMap<TemplateReflection>
     * @phpstan-return NameMap<TemplateReflection>
     */
    public function templates(): NameMap
    {
        return $this->templates ??= (new NameMap($this->data[Data::Templates]))->map(
            fn(TypedMap $data, string $name): TemplateReflection => new TemplateReflection(Id::template($this->id, $name), $data),
        );
    }

    /**
     * @return AttributeReflection[]
     * @psalm-return ListOf<AttributeReflection>
     * @phpstan-return ListOf<AttributeReflection>
     */
    public function attributes(): ListOf
    {
        return $this->attributes ??= (new ListOf($this->data[Data::Attributes]))->map(
            fn(TypedMap $data, int $index): AttributeReflection => new AttributeReflection($this->id, $index, $data, $this->reflector),
        );
    }

    /**
     * @return ParameterReflection[]
     * @psalm-return NameMap<ParameterReflection>
     * @phpstan-return NameMap<ParameterReflection>
     */
    public function parameters(): NameMap
    {
        return $this->parameters ??= (new NameMap($this->data[Data::Parameters]))->map(
            fn(TypedMap $data, string $name): ParameterReflection => new ParameterReflection(Id::parameter($this->id, $name), $data, $this->reflector),
        );
    }

    /**
     * @return ?non-empty-string
     */
    public function phpDoc(): ?string
    {
        return $this->data[Data::PhpDoc];
    }

    /**
     * @return ?non-empty-string
     */
    public function extension(): ?string
    {
        return $this->data[Data::PhpExtension];
    }

    public function namespace(): string
    {
        return $this->data[Data::Namespace];
    }

    /**
     * @return non-empty-string
     */
    public function shortName(): string
    {
        if ($this->name === null) {
            return $this->id->describe();
        }

        $lastSlashPosition = strrpos($this->name, '\\');

        if ($lastSlashPosition === false) {
            return $this->name;
        }

        $shortName = substr($this->name, $lastSlashPosition + 1);
        \assert($shortName !== '', 'A valid function name cannot end with a backslash');

        return $shortName;
    }

    /**
     * @return ?non-empty-string
     */
    public function file(): ?string
    {
        return $this->data[Data::File];
    }

    /**
     * @return ?positive-int
     */
    public function startLine(): ?int
    {
        return $this->data[Data::StartLine];
    }

    /**
     * @return ?positive-int
     */
    public function endLine(): ?int
    {
        return $this->data[Data::EndLine];
    }

    public function changeDetector(): ChangeDetector
    {
        return $this->data[Data::ChangeDetector];
    }

    public function isInternallyDefined(): bool
    {
        return $this->data[Data::InternallyDefined];
    }

    public function isGenerator(): bool
    {
        return $this->data[Data::Generator];
    }

    /**
     * @psalm-assert-if-false !null $this->name
     */
    public function isAnonymous(): bool
    {
        return $this->id instanceof AnonymousFunctionId;
    }

    public function isStatic(): bool
    {
        return $this->data[Data::Static];
    }

    public function isVariadic(): bool
    {
        $lastParameterName = array_key_last($this->parameters()->names());

        return $lastParameterName !== null && $this->parameters()[$lastParameterName]->isVariadic();
    }

    public function returnsReference(): bool
    {
        return $this->data[Data::ByReference];
    }

    public function returnType(Kind $kind = Kind::Resolved): ?Type
    {
        return $this->data[Data::Type]->byKind($kind);
    }

    public function throwsType(): ?Type
    {
        return $this->data[Data::ThrowsType];
    }

    private ?FunctionAdapter $native = null;

    public function toNative(): \ReflectionFunction
    {
        return $this->native ??= new FunctionAdapter($this);
    }
}
