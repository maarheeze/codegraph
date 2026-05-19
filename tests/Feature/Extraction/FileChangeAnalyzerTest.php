<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Extraction;

use Maarheeze\CodeGraph\Extraction\FileChangeAnalyzer;
use Maarheeze\CodeGraph\Storage\Sqlite\SqliteGraph;
use Maarheeze\CodeGraph\Tests\Feature\FeatureTestCase;
use Maarheeze\CodeGraph\Values\IndexStats;

final class FileChangeAnalyzerTest extends FeatureTestCase
{
    public function testNewFileIsDetectedAsChanged(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $analyzer = new FileChangeAnalyzer($graph);
        $stats = new IndexStats();

        $fileData = [
            'relPath' => 'app/User.php',
            'mtime' => 1234567890,
            'size' => 1024,
            'sha256' => 'abc123',
        ];

        $result = $analyzer->hasChanged($fileData, $stats);

        self::assertTrue($result);
        self::assertSame(1, $stats->getFilesChanged());
    }

    public function testUnchangedFileIsSkipped(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $analyzer = new FileChangeAnalyzer($graph);
        $stats = new IndexStats();

        $graph->recordFile('app/User.php', 'abc123', 1024, 1234567890, [], [], []);

        $fileData = [
            'relPath' => 'app/User.php',
            'mtime' => 1234567890,
            'size' => 1024,
            'sha256' => 'abc123',
        ];

        $result = $analyzer->hasChanged($fileData, $stats);

        self::assertFalse($result);
        self::assertSame(1, $stats->getFilesSkipped());
    }

    public function testChangedMtimeWithChangedSizeDetectsChange(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $analyzer = new FileChangeAnalyzer($graph);
        $stats = new IndexStats();

        $graph->recordFile('app/User.php', 'abc123', 1024, 1234567890, [], [], []);

        $fileData = [
            'relPath' => 'app/User.php',
            'mtime' => 1234567900,
            'size' => 2048,
            'sha256' => 'xyz789',
        ];

        $result = $analyzer->hasChanged($fileData, $stats);

        self::assertTrue($result);
        self::assertSame(1, $stats->getFilesChanged());
    }

    public function testChangedSizeDetectsChange(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $analyzer = new FileChangeAnalyzer($graph);
        $stats = new IndexStats();

        $graph->recordFile('app/User.php', 'abc123', 1024, 1234567890, [], [], []);

        $fileData = [
            'relPath' => 'app/User.php',
            'mtime' => 1234567890,
            'size' => 2048,
            'sha256' => 'xyz789',
        ];

        $result = $analyzer->hasChanged($fileData, $stats);

        self::assertTrue($result);
        self::assertSame(1, $stats->getFilesChanged());
    }

    public function testSameSha256WithChangedMtimeIsNotChanged(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $analyzer = new FileChangeAnalyzer($graph);
        $stats = new IndexStats();

        $graph->recordFile('app/User.php', 'abc123', 1024, 1234567890, [], [], []);

        $fileData = [
            'relPath' => 'app/User.php',
            'mtime' => 1234567900,
            'size' => 2048,
            'sha256' => 'abc123',
        ];

        $result = $analyzer->hasChanged($fileData, $stats);

        self::assertFalse($result);
        self::assertSame(1, $stats->getFilesSkipped());
    }

    public function testDifferentSha256WithDifferentMtimeDetectsChange(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $analyzer = new FileChangeAnalyzer($graph);
        $stats = new IndexStats();

        $graph->recordFile('app/User.php', 'abc123', 1024, 1234567890, [], [], []);

        $fileData = [
            'relPath' => 'app/User.php',
            'mtime' => 1234567900,
            'size' => 1024,
            'sha256' => 'xyz789',
        ];

        $result = $analyzer->hasChanged($fileData, $stats);

        self::assertTrue($result);
        self::assertSame(1, $stats->getFilesChanged());
    }
}
