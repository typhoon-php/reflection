<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\PHPDocParser;

use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection
 */
final class PHPDoc
{
    /**
     * @param array<non-empty-string, TypeNode> $paramTypes
     * @param array<non-empty-string, TemplateTagValueNode> $templates
     * @param array<non-empty-string, GenericTypeNode> $inheritedTypes
     */
    public function __construct(
        public readonly ?TypeNode $varType = null,
        public readonly array $paramTypes = [],
        public readonly ?TypeNode $returnType = null,
        public readonly array $templates = [],
        public readonly array $inheritedTypes = [],
    ) {
    }

    public function varType(): ?TypeNode
    {
        return $this->varType;
    }

    public function paramType(string $name): ?TypeNode
    {
        return $this->paramTypes[$name] ?? null;
    }

    public function returnType(): ?TypeNode
    {
        return $this->returnType;
    }

    /**
     * @return list<TemplateTagValueNode>
     */
    public function templates(): array
    {
        return array_values($this->templates);
    }

    /**
     * @return list<GenericTypeNode>
     */
    public function inheritedTypes(): array
    {
        return array_values($this->inheritedTypes);
    }
}
