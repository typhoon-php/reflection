<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Context;

use PhpParser\ErrorHandler\Throwing;
use PhpParser\NameContext;
use PhpParser\Node\Stmt\Use_;
use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\DeclarationId\TemplateId;
use Typhoon\Reflection\Internal\PhpParser\NameParser;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class Context
{
    /**
     * @param ?non-empty-string $file
     * @param array<non-empty-string, AliasId> $aliases
     * @param array<non-empty-string, TemplateId> $templates
     */
    private function __construct(
        public readonly ?string $file,
        private NameContext $nameContext,
        public readonly null|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId|MethodId $site = null,
        public readonly null|NamedClassId|AnonymousClassId $self = null,
        public readonly ?NamedClassId $trait = null,
        public readonly ?NamedClassId $parent = null,
        private readonly array $aliases = [],
        private readonly array $templates = [],
    ) {}

    /**
     * @param ?non-empty-string $file
     */
    public static function start(?string $file = null): self
    {
        $nameContext = new NameContext(new Throwing());
        $nameContext->startNamespace();

        return new self($file, $nameContext);
    }

    public function withNameContext(NameContext $nameContext): self
    {
        $context = clone $this;
        $context->nameContext = clone $nameContext;

        return $context;
    }

    /**
     * @param non-empty-string $name
     * @param list<non-empty-string> $templateNames
     */
    public function enterFunction(string $name, array $templateNames = []): self
    {
        $site = Id::namedFunction($name);

        return new self(
            file: $this->file,
            nameContext: $this->nameContext,
            site: $site,
            aliases: $this->aliases,
            templates: self::templatesFromNames($site, $templateNames),
        );
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @param positive-int $line
     * @param positive-int $column
     * @param list<non-empty-string> $templateNames
     */
    public function enterAnonymousFunction(int $line, int $column, array $templateNames = []): self
    {
        \assert($this->file !== null);
        $site = Id::anonymousFunction($this->file, $line, $column);

        return new self(
            file: $this->file,
            nameContext: $this->nameContext,
            site: $site,
            self: $this->self,
            trait: $this->trait,
            parent: $this->parent,
            aliases: $this->aliases,
            templates: [
                ...$this->templates,
                ...self::templatesFromNames($site, $templateNames),
            ],
        );
    }

    /**
     * @param non-empty-string $name
     * @param ?non-empty-string $parentName
     * @param list<non-empty-string> $aliasNames
     * @param list<non-empty-string> $templateNames
     */
    public function enterClass(
        string $name,
        ?string $parentName = null,
        array $aliasNames = [],
        array $templateNames = [],
    ): self {
        $site = Id::namedClass($name);

        return new self(
            file: $this->file,
            nameContext: $this->nameContext,
            site: $site,
            self: $site,
            parent: $parentName === null ? null : Id::namedClass($parentName),
            aliases: [
                ...$this->aliases,
                ...self::aliasesFromNames($site, $aliasNames),
            ],
            templates: self::templatesFromNames($site, $templateNames),
        );
    }

    /**
     * @param positive-int $line
     * @param positive-int $column
     * @param ?non-empty-string $parentName
     * @param list<non-empty-string> $aliasNames
     * @param list<non-empty-string> $templateNames
     */
    public function enterAnonymousClass(
        int $line,
        int $column,
        ?string $parentName = null,
        array $aliasNames = [],
        array $templateNames = [],
    ): self {
        \assert($this->file !== null);
        $site = Id::anonymousClass($this->file, $line, $column);

        return new self(
            file: $this->file,
            nameContext: $this->nameContext,
            site: $site,
            self: $site,
            parent: $parentName === null ? null : Id::namedClass($parentName),
            aliases: [
                ...$this->aliases,
                ...self::aliasesFromNames($site, $aliasNames),
            ],
            templates: self::templatesFromNames($site, $templateNames),
        );
    }

    /**
     * @param non-empty-string $name
     * @param list<non-empty-string> $aliasNames
     * @param list<non-empty-string> $templateNames
     */
    public function enterInterface(string $name, array $aliasNames = [], array $templateNames = []): self
    {
        $site = Id::namedClass($name);

        return new self(
            file: $this->file,
            nameContext: $this->nameContext,
            site: $site,
            self: $site,
            aliases: [
                ...$this->aliases,
                ...self::aliasesFromNames($site, $aliasNames),
            ],
            templates: self::templatesFromNames($site, $templateNames),
        );
    }

    /**
     * @param non-empty-string $name
     * @param list<non-empty-string> $aliasNames
     * @param list<non-empty-string> $templateNames
     */
    public function enterEnum(string $name, array $aliasNames = [], array $templateNames = []): self
    {
        $site = Id::namedClass($name);

        return new self(
            file: $this->file,
            nameContext: $this->nameContext,
            site: $site,
            self: $site,
            aliases: [
                ...$this->aliases,
                ...self::aliasesFromNames($site, $aliasNames),
            ],
            templates: self::templatesFromNames($site, $templateNames),
        );
    }

    /**
     * @param non-empty-string $name
     * @param list<non-empty-string> $aliasNames
     * @param list<non-empty-string> $templateNames
     */
    public function enterTrait(string $name, array $aliasNames = [], array $templateNames = []): self
    {
        $site = Id::namedClass($name);

        return new self(
            file: $this->file,
            nameContext: $this->nameContext,
            site: $site,
            trait: $site,
            aliases: [
                ...$this->aliases,
                ...self::aliasesFromNames($site, $aliasNames),
            ],
            templates: self::templatesFromNames($site, $templateNames),
        );
    }

    /**
     * @param non-empty-string $name
     * @param list<non-empty-string> $templateNames
     */
    public function enterMethod(string $name, array $templateNames): self
    {
        \assert($this->site instanceof NamedClassId || $this->site instanceof AnonymousClassId);
        $site = Id::method($this->site, $name);

        return new self(
            file: $this->file,
            nameContext: $this->nameContext,
            site: $site,
            self: $this->self,
            trait: $this->trait,
            parent: $this->parent,
            aliases: $this->aliases,
            templates: [
                ...$this->templates,
                ...self::templatesFromNames($site, $templateNames),
            ],
        );
    }

    public function namespace(): string
    {
        return $this->nameContext->getNamespace()?->toString() ?? '';
    }

    /**
     * @param non-empty-string $unresolvedName
     * @return array{non-empty-string, ?non-empty-string}
     */
    public function resolveConstantName(string $unresolvedName): array
    {
        $resolved = $this->nameContext->getResolvedName(NameParser::parse($unresolvedName), Use_::TYPE_CONSTANT);

        if ($resolved !== null) {
            return [$resolved->toString(), null];
        }

        return [$this->namespace() . '\\' . $unresolvedName, $unresolvedName];
    }

    /**
     * @param non-empty-string $unresolvedName
     * @return non-empty-string
     */
    public function resolveClassName(string $unresolvedName): string
    {
        return $this->nameContext->getResolvedClassName(NameParser::parse($unresolvedName))->toString();
    }

    /**
     * @param non-empty-string $unresolvedName
     * @param list<Type> $arguments
     */
    public function resolveNameAsType(string $unresolvedName, array $arguments = []): Type
    {
        if (str_contains($unresolvedName, '\\')) {
            return types::object($this->resolveClassName($unresolvedName), $arguments);
        }

        $type = match (strtolower($unresolvedName)) {
            'self' => types::self($arguments, $this->self),
            'parent' => types::parent($arguments, $this->parent),
            'static' => types::static($arguments, $this->self),
            default => null,
        };

        if ($type !== null) {
            return $type;
        }

        if (isset($this->aliases[$unresolvedName])) {
            return types::alias($this->aliases[$unresolvedName], $arguments);
        }

        if (isset($this->templates[$unresolvedName])) {
            if ($arguments !== []) {
                throw new \LogicException('Template type arguments are not supported');
            }

            return types::template($this->templates[$unresolvedName]);
        }

        return types::object($this->resolveClassName($unresolvedName), $arguments);
    }

    /**
     * @param list<non-empty-string> $names
     * @return array<non-empty-string, AliasId>
     */
    private static function aliasesFromNames(NamedClassId|AnonymousClassId $class, array $names): array
    {
        return array_combine($names, array_map(
            static fn(string $templateName): AliasId => Id::alias($class, $templateName),
            $names,
        ));
    }

    /**
     * @param list<non-empty-string> $names
     * @return array<non-empty-string, TemplateId>
     */
    private static function templatesFromNames(
        NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId|MethodId $site,
        array $names,
    ): array {
        return array_combine($names, array_map(
            static fn(string $templateName): TemplateId => Id::template($site, $templateName),
            $names,
        ));
    }
}
