<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

return static function (TyphoonReflector $reflector): void {
    $properties = $reflector
        ->withResource(Resource::fromCode(
            <<<'PHP'
                <?php
                final class A
                {
                    public string $notReadonly;
                    
                    public readonly string $nativeReadonly;
                    
                    /** @readonly */
                    public string $phpDocReadonly;
                    
                    /** @readonly */
                    public readonly string $nativeAndPhpDocReadonly;
                }
                PHP,
        ))
        ->reflectClass('A')
        ->properties();

    $notReadonly = $properties['notReadonly'];
    assertFalse($notReadonly->isReadonly(ModifierKind::Native));
    assertFalse($notReadonly->isReadonly(ModifierKind::Annotated));
    assertFalse($notReadonly->isReadonly());

    $nativeReadonly = $properties['nativeReadonly'];
    assertTrue($nativeReadonly->isReadonly(ModifierKind::Native));
    assertFalse($nativeReadonly->isReadonly(ModifierKind::Annotated));
    assertTrue($nativeReadonly->isReadonly());

    $phpDocReadonly = $properties['phpDocReadonly'];
    assertFalse($phpDocReadonly->isReadonly(ModifierKind::Native));
    assertTrue($phpDocReadonly->isReadonly(ModifierKind::Annotated));
    assertTrue($phpDocReadonly->isReadonly());

    $nativeAndPhpDocReadonly = $properties['nativeAndPhpDocReadonly'];
    assertTrue($nativeAndPhpDocReadonly->isReadonly(ModifierKind::Native));
    assertTrue($nativeAndPhpDocReadonly->isReadonly(ModifierKind::Annotated));
    assertTrue($nativeAndPhpDocReadonly->isReadonly());
};
