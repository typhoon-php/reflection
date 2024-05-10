<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\TypeContext;

use PhpParser\ErrorHandler\Throwing;
use PhpParser\NameContext;
use PhpParser\Node\Name;
use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\DeclarationId;
use Typhoon\DeclarationId\TemplateId;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class TypeContext
{
    /**
     * @var array<non-empty-string, AliasId>
     */
    public readonly array $aliases;

    /**
     * @var array<non-empty-string, TemplateId>
     */
    public readonly array $templates;

    private readonly NameContext $nameContext;

    /**
     * @param array<AliasId> $aliases
     * @param array<TemplateId> $templates
     */
    public function __construct(
        ?NameContext $nameContext = null,
        public readonly ?DeclarationId $id = null,
        public readonly null|ClassId|AnonymousClassId $self = null,
        public readonly ?ClassId $parent = null,
        array $aliases = [],
        array $templates = [],
    ) {
        if ($nameContext === null) {
            $this->nameContext = new NameContext(new Throwing());
            $this->nameContext->startNamespace();
        } else {
            $this->nameContext = clone $nameContext;
        }

        $this->aliases = array_column($aliases, null, 'name');
        $this->templates = array_column($templates, null, 'name');
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function namespace(): ?Name
    {
        return $this->nameContext->getNamespace();
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function resolveClass(Name $name): Name
    {
        return $this->nameContext->getResolvedClassName($name);
    }

    /**
     * @param list<Type> $arguments
     */
    public function resolveType(Name $name, array $arguments = []): Type
    {
        if ($name->isSpecialClassName()) {
            return match ($name->toLowerString()) {
                'self' => types::self($this->self, ...$arguments),
                'parent' => types::parent($this->parent, ...$arguments),
                default => types::static($this->self, ...$arguments),
            };
        }

        $stringName = $name->toString();

        if (isset($this->aliases[$stringName])) {
            return types::alias($this->aliases[$stringName], ...$arguments);
        }

        if (isset($this->templateNames[$stringName])) {
            if ($arguments !== []) {
                throw new \LogicException('Template type arguments are not supported');
            }

            return types::template($this->templates[$stringName]);
        }

        return types::object($this->nameContext->getResolvedClassName($name)->toString(), ...$arguments);
    }
}
