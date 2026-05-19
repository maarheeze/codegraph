<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature;

use Maarheeze\CodeGraph\CodeGraph;

final class CodeGraphTest extends FeatureTestCase
{
    public function testForProjectWithExistingSrcDirectory(): void
    {
        $codeGraph = CodeGraph::forProject(__DIR__ . '/../..');

        self::assertContains('src', $codeGraph->getScanPaths());
    }

    public function testGetStorageReturnsStorageInterface(): void
    {
        $codeGraph = new CodeGraph(
            __DIR__,
            ':memory:',
            ['src'],
        );

        $storage = $codeGraph->getStorage();

        self::assertNotNull($storage);
    }

    public function testStatsReturnsArrayWithAllKeys(): void
    {
        $codeGraph = new CodeGraph(
            __DIR__,
            ':memory:',
            ['src'],
        );

        $stats = $codeGraph->stats();

        self::assertArrayHasKey('symbols', $stats);
        self::assertArrayHasKey('edges', $stats);
        self::assertArrayHasKey('chunks', $stats);
        self::assertArrayHasKey('files', $stats);
    }

    public function testStatsReturnsZerosForEmptyDatabase(): void
    {
        $codeGraph = new CodeGraph(
            __DIR__,
            ':memory:',
            ['src'],
        );

        $stats = $codeGraph->stats();

        self::assertSame(0, $stats['symbols']);
        self::assertSame(0, $stats['edges']);
        self::assertSame(0, $stats['chunks']);
        self::assertSame(0, $stats['files']);
    }

    public function testGraphLazyInitializesAndMigrates(): void
    {
        $codeGraph = new CodeGraph(
            __DIR__,
            ':memory:',
            ['src'],
        );

        $storage1 = $codeGraph->getStorage();
        $storage2 = $codeGraph->getStorage();

        self::assertSame($storage1, $storage2);
    }
}
