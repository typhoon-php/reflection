<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\TypeContext;

use PhpParser\ErrorHandler\Throwing;
use PhpParser\NameContext;
use PhpParser\Node\Name;
use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
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
        public readonly ?Id $id = null,
        public readonly null|NamedClassId|AnonymousClassId $self = null,
        public readonly ?NamedClassId $parent = null,
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

    public function namespace(): ?Name
    {
        return $this->nameContext->getNamespace();
    }

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
                'self' => types::self($arguments, $this->self),
                'parent' => types::parent($arguments, $this->parent),
                default => types::static($arguments, $this->self),
            };
        }

        $stringName = $name->toString();

        if (isset($this->aliases[$stringName])) {
            return types::alias($this->aliases[$stringName], $arguments);
        }

        if (isset($this->templates[$stringName])) {
            if ($arguments !== []) {
                throw new \LogicException('Template type arguments are not supported');
            }

            return types::template($this->templates[$stringName]);
        }

        return types::object($this->nameContext->getResolvedClassName($name)->toString(), $arguments);
    }
}
