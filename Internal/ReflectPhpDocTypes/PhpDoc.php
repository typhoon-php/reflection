<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ReflectPhpDocTypes;

use PHPStan\PhpDocParser\Ast\PhpDoc\DeprecatedTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ImplementsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ThrowsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasImportTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\UsesTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Typhoon\Type\Variance;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PhpDoc
{
    private const VARIANCE_ATTRIBUTE = 'variance';

    private static ?self $empty = null;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection\PhpDocParser
     * @param array<PhpDocTagNode> $tags
     */
    public function __construct(
        private readonly TagPrioritizer $tagPrioritizer,
        private array $tags,
    ) {}

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function empty(): self
    {
        return self::$empty ??= new self(
            tagPrioritizer: new PrefixBasedTagPrioritizer(),
            tags: [],
        );
    }

    public static function templateTagVariance(TemplateTagValueNode $tag): Variance
    {
        $attribute = $tag->getAttribute(self::VARIANCE_ATTRIBUTE);

        return $attribute instanceof Variance ? $attribute : Variance::Invariant;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function hasDeprecated(): bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->value instanceof DeprecatedTagValueNode) {
                return true;
            }
        }

        return false;
    }

    public function hasFinal(): bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->name === '@final') {
                return true;
            }
        }

        return false;
    }

    public function hasReadonly(): bool
    {
        foreach ($this->tags as $tag) {
            if (\in_array($tag->name, ['@readonly', '@psalm-readonly', '@phpstan-readonly'], true)) {
                return true;
            }
        }

        return false;
    }

    public function varType(): ?TypeNode
    {
        $varTag = null;

        foreach ($this->tags as $tag) {
            if (!$tag->value instanceof VarTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<VarTagValueNode> $tag */
            if ($this->shouldReplaceTag($varTag, $tag)) {
                $varTag = $tag;
            }
        }

        return $varTag?->value->type;
    }

    /**
     * @return array<non-empty-string, TypeNode>
     */
    public function paramTypes(): array
    {
        $paramTags = [];

        foreach ($this->tags as $tag) {
            if (!$tag->value instanceof ParamTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<ParamTagValueNode> $tag */
            $name = $tag->value->parameterName;
            \assert(($name[0] ?? '') === '$');
            $name = substr($name, 1);
            \assert($name !== '');

            if ($this->shouldReplaceTag($paramTags[$name] ?? null, $tag)) {
                $paramTags[$name] = $tag;
            }
        }

        return array_map(
            static fn(PhpDocTagNode $tag): TypeNode => $tag->value->type,
            $paramTags,
        );
    }

    public function returnType(): ?TypeNode
    {
        $returnTag = null;

        foreach ($this->tags as $tag) {
            if (!$tag->value instanceof ReturnTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<ReturnTagValueNode> $tag */
            if ($this->shouldReplaceTag($returnTag, $tag)) {
                $returnTag = $tag;
            }
        }

        return $returnTag?->value->type;
    }

    /**
     * @return list<TypeNode>
     */
    public function throwsTypes(): array
    {
        $throwsTypes = [];

        foreach ($this->tags as $tag) {
            if (!$tag->value instanceof ThrowsTagValueNode) {
                continue;
            }

            $throwsTypes[] = $tag->value->type;
        }

        return $throwsTypes;
    }

    /**
     * @return list<TemplateTagValueNode>
     */
    public function templates(): array
    {
        $templateTags = [];

        foreach ($this->tags as $tag) {
            if (!$tag->value instanceof TemplateTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<TemplateTagValueNode> $tag */
            if ($this->shouldReplaceTag($templateTags[$tag->value->name] ?? null, $tag)) {
                $templateTags[$tag->value->name] = $tag;
            }
        }

        return array_map(
            static function (PhpDocTagNode $tag): TemplateTagValueNode {
                $tag->value->setAttribute(self::VARIANCE_ATTRIBUTE, match (true) {
                    str_ends_with($tag->name, 'covariant') => Variance::Covariant,
                    str_ends_with($tag->name, 'contravariant') => Variance::Contravariant,
                    default => Variance::Invariant,
                });

                return $tag->value;
            },
            array_values($templateTags),
        );
    }

    /**
     * @return list<GenericTypeNode>
     */
    public function extendedTypes(): array
    {
        $extendsTags = [];

        foreach ($this->tags as $tag) {
            if (!$tag->value instanceof ExtendsTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<ExtendsTagValueNode> $tag */
            $name = $tag->value->type->type->name;

            if ($this->shouldReplaceTag($extendsTags[$name] ?? null, $tag)) {
                $extendsTags[$name] = $tag;
            }
        }

        return array_map(
            static fn(PhpDocTagNode $tag): GenericTypeNode => $tag->value->type,
            array_values($extendsTags),
        );
    }

    /**
     * @return list<GenericTypeNode>
     */
    public function implementedTypes(): array
    {
        $implementsTags = [];

        foreach ($this->tags as $tag) {
            if (!$tag->value instanceof ImplementsTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<ImplementsTagValueNode> $tag */
            $name = $tag->value->type->type->name;

            if ($this->shouldReplaceTag($implementsTags[$name] ?? null, $tag)) {
                $implementsTags[$name] = $tag;
            }
        }

        return array_map(
            static fn(PhpDocTagNode $tag): GenericTypeNode => $tag->value->type,
            array_values($implementsTags),
        );
    }

    /**
     * @return list<GenericTypeNode>
     */
    public function usedTypes(): array
    {
        $tagsByName = [];

        foreach ($this->tags as $tag) {
            if (!$tag->value instanceof UsesTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<UsesTagValueNode> $tag */
            $name = $tag->value->type->type->name;

            if ($this->shouldReplaceTag($tagsByName[$name] ?? null, $tag)) {
                $tagsByName[$name] = $tag;
            }
        }

        return array_map(
            static fn(PhpDocTagNode $tag): GenericTypeNode => $tag->value->type,
            array_values($tagsByName),
        );
    }

    /**
     * @return list<TypeAliasTagValueNode>
     */
    public function typeAliases(): array
    {
        $typeAliasesByAlias = [];

        foreach ($this->tags as $tag) {
            if (!$tag->value instanceof TypeAliasTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<TypeAliasTagValueNode> $tag */
            if ($this->shouldReplaceTag($typeAliasesByAlias[$tag->value->alias] ?? null, $tag)) {
                $typeAliasesByAlias[$tag->value->alias] = $tag;
            }
        }

        return array_column($typeAliasesByAlias, 'value');
    }

    /**
     * @return list<TypeAliasImportTagValueNode>
     */
    public function typeAliasImports(): array
    {
        $typeAliasImportsByAlias = [];

        foreach ($this->tags as $tag) {
            if (!$tag->value instanceof TypeAliasImportTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<TypeAliasImportTagValueNode> $tag */
            $alias = $tag->value->importedAs ?? $tag->value->importedAlias;

            if ($this->shouldReplaceTag($typeAliasImportsByAlias[$alias] ?? null, $tag)) {
                $typeAliasImportsByAlias[$alias] = $tag;
            }
        }

        return array_column($typeAliasImportsByAlias, 'value');
    }

    /**
     * @template TCurrentValueNode of PhpDocTagValueNode
     * @template TNewValueNode of PhpDocTagValueNode
     * @param PhpDocTagNode<TCurrentValueNode> $currentTag
     * @param PhpDocTagNode<TNewValueNode> $newTag
     */
    private function shouldReplaceTag(?PhpDocTagNode $currentTag, PhpDocTagNode $newTag): bool
    {
        return $currentTag === null || $this->priorityOf($newTag) >= $this->priorityOf($currentTag);
    }

    /**
     * @template TValueNode of PhpDocTagValueNode
     * @param PhpDocTagNode<TValueNode> $tag
     */
    private function priorityOf(PhpDocTagNode $tag): int
    {
        $priority = $tag->getAttribute('priority');

        if (!\is_int($priority)) {
            $priority = $this->tagPrioritizer->priorityFor($tag->name);
            $tag->setAttribute('priority', $priority);
        }

        return $priority;
    }
}
