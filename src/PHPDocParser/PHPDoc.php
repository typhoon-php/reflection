<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\PHPDocParser;

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
     * @param list<PhpDocTagNode> $tags
     */
    public function __construct(
        private readonly array $tags = [],
    ) {
    }

    public function varType(): ?TypeNode
    {
        foreach ($this->tags as $tag) {
            if ($tag->value instanceof VarTagValueNode) {
                return $tag->value->type;
            }
        }

        return null;
    }

    public function paramType(string $name): ?TypeNode
    {
        $dollarName = '$' . $name;

        foreach ($this->tags as $tag) {
            if ($tag->value instanceof ParamTagValueNode && $tag->value->parameterName === $dollarName) {
                return $tag->value->type;
            }
        }

        return null;
    }

    public function returnType(): ?TypeNode
    {
        foreach ($this->tags as $tag) {
            if ($tag->value instanceof ReturnTagValueNode) {
                return $tag->value->type;
            }
        }

        return null;
    }

    /**
     * @return list<PhpDocTagNode<TemplateTagValueNode>>
     */
    public function templateTags(): array
    {
        $templates = [];

        foreach ($this->tags as $tag) {
            if ($tag->value instanceof TemplateTagValueNode && !isset($templates[$tag->value->name])) {
                /** @var PhpDocTagNode<TemplateTagValueNode> */
                $templates[$tag->value->name] = $tag;
            }
        }

        return array_values($templates);
    }

    /**
     * @return list<GenericTypeNode>
     */
    public function inheritedTypes(): array
    {
        $types = [];

        foreach ($this->tags as $tag) {
            if (!($tag->value instanceof ExtendsTagValueNode || $tag->value instanceof ImplementsTagValueNode)) {
                continue;
            }

            $class = (string) $tag->value->type->type;

            if (isset($types[(string) $tag->value->type->type])) {
                continue;
            }

            $types[$class] = $tag->value->type;
        }

        return array_values($types);
    }
}
