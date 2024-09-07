<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpDoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PrefixBasedPhpDocTagPrioritizer::class)]
final class PrefixBasedTagPrioritizerTest extends TestCase
{
    public function testPsalmTagHasHigherPriorityOverPHPStanTag(): void
    {
        $prioritizer = new PrefixBasedPhpDocTagPrioritizer();

        $psalmPriority = $prioritizer->priorityFor('@psalm-var');
        $phpStanPriority = $prioritizer->priorityFor('@phpstan-var');

        self::assertGreaterThan($phpStanPriority, $psalmPriority);
    }

    public function testPHPStanTagHasHigherPriorityOverStandardTag(): void
    {
        $prioritizer = new PrefixBasedPhpDocTagPrioritizer();

        $standardTagPriority = $prioritizer->priorityFor('@var');
        $phpStanPriority = $prioritizer->priorityFor('@phpstan-var');

        self::assertGreaterThan($standardTagPriority, $phpStanPriority);
    }
}
