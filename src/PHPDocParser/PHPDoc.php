<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\PHPDocParser;

use ExtendedTypeSystem\Reflection\TagPrioritizer;
use ExtendedTypeSystem\Reflection\Variance;
use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ImplementsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection
 */
final class PHPDoc
{
    /**
     * @param array<PhpDocTagNode> $tags
     */
    public function __construct(
        private readonly TagPrioritizer $tagPrioritizer,
        private readonly array $tags = [],
    ) {
    }

    public function varType(): ?TypeNode
    {
        $typesByPriority = [];

        foreach ($this->tags as $tag) {
            if ($tag->value instanceof VarTagValueNode) {
                $typesByPriority[$this->prioritizeTag($tag)] = $tag->value->type;
            }
        }

        krsort($typesByPriority);

        return reset($typesByPriority) ?: null;
    }

    public function paramType(string $name): ?TypeNode
    {
        $dollarName = '$' . $name;
        $typesByPriority = [];

        foreach ($this->tags as $tag) {
            if ($tag->value instanceof ParamTagValueNode && $tag->value->parameterName === $dollarName) {
                $typesByPriority[$this->prioritizeTag($tag)] = $tag->value->type;
            }
        }

        krsort($typesByPriority);

        return reset($typesByPriority) ?: null;
    }

    public function returnType(): ?TypeNode
    {
        $typesByPriority = [];

        foreach ($this->tags as $tag) {
            if ($tag->value instanceof ReturnTagValueNode) {
                $typesByPriority[$this->prioritizeTag($tag)] = $tag->value->type;
            }
        }

        krsort($typesByPriority);

        return reset($typesByPriority) ?: null;
    }

    /**
     * @return list<TemplateTagValueNode>
     */
    public function templates(): array
    {
        /** @var array<non-empty-string, non-empty-array<int, PhpDocTagNode<TemplateTagValueNode>>> */
        $tagsByNameByPriority = [];

        foreach ($this->tags as $tag) {
            if ($tag->value instanceof TemplateTagValueNode) {
                /** @var PhpDocTagNode<TemplateTagValueNode> */
                $tagsByNameByPriority[$tag->value->name][$this->prioritizeTag($tag)] = $tag;
            }
        }

        return array_values(
            array_map(
                static function (array $tags): TemplateTagValueNode {
                    krsort($tags);
                    $tag = reset($tags);
                    $tag->value->setAttribute('variance', match (true) {
                        str_ends_with($tag->name, 'covariant') => Variance::COVARIANT,
                        str_ends_with($tag->name, 'contravariant') => Variance::CONTRAVARIANT,
                        default => Variance::INVARIANT,
                    });

                    return $tag->value;
                },
                $tagsByNameByPriority,
            ),
        );
    }

    /**
     * @return list<GenericTypeNode>
     */
    public function inheritedTypes(): array
    {
        /** @var array<string, non-empty-array<int, GenericTypeNode>> */
        $typesByClassByPriority = [];

        foreach ($this->tags as $tag) {
            if ($tag->value instanceof ExtendsTagValueNode || $tag->value instanceof ImplementsTagValueNode) {
                $typesByClassByPriority[(string) $tag->value->type->type][$this->prioritizeTag($tag)] = $tag->value->type;
            }
        }

        return array_values(
            array_map(
                static function (array $tags): GenericTypeNode {
                    krsort($tags);

                    return reset($tags);
                },
                $typesByClassByPriority,
            ),
        );
    }

    private function prioritizeTag(PhpDocTagNode $tag): int
    {
        return $this->tagPrioritizer->priorityFor($tag->name);
    }
}
