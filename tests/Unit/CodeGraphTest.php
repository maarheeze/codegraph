<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Unit;

use Maarheeze\CodeGraph\CodeGraph;
use PHPUnit\Framework\TestCase;

final class CodeGraphTest extends TestCase
{
    public function testGetScanPathsReturnsConfiguredPaths(): void
    {
        $codeGraph = new CodeGraph(
            __DIR__,
            '/tmp/test.db',
            ['src', 'lib'],
            [],
        );

        self::assertSame(['src', 'lib'], $codeGraph->getScanPaths());
    }

    public function testConstructorWithDefaultScanPaths(): void
    {
        $codeGraph = new CodeGraph(
            __DIR__,
            '/tmp/test.db',
        );

        self::assertSame(['src', 'app'], $codeGraph->getScanPaths());
    }

    public function testConstructorWithDefaultExcludes(): void
    {
        $codeGraph = new CodeGraph(
            __DIR__,
            '/tmp/test.db',
            ['src'],
            ['vendor', 'node_modules'],
        );

        self::assertSame(['src'], $codeGraph->getScanPaths());
    }

    public function testGetStorageReturnsValidStorage(): void
    {
        $codeGraph = new CodeGraph(
            __DIR__,
            '/tmp/test.db',
            ['src'],
        );

        $storage = $codeGraph->getStorage();
        self::assertNotNull($storage);
    }
}
