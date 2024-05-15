<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\DeclarationId\MethodId;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\templateId;

/**
 * @api
 * @extends Reflections<int|string, TemplateReflection>
 */
final class TemplateReflections extends Reflections
{
    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param array<non-empty-string, TypedMap> $data
     */
    public function __construct(
        private readonly FunctionId|ClassId|AnonymousClassId|MethodId $declaredAt,
        array $data,
        private readonly Reflector $reflector,
    ) {
        parent::__construct($data);
    }

    protected function load(string $name, TypedMap $data): Reflection
    {
        return new TemplateReflection(templateId($this->declaredAt, $name), $data, $this->reflector);
    }
}
