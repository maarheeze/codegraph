<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Extraction;

use FilesystemIterator;
use Maarheeze\CodeGraph\Extraction\Indexer;
use Maarheeze\CodeGraph\Tests\Feature\FeatureTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function file_put_contents;
use function is_dir;
use function mkdir;
use function random_int;
use function rmdir;
use function sprintf;
use function sys_get_temp_dir;
use function unlink;

final class IndexerEdgeCasesTest extends FeatureTestCase
{
    public function testRunIndexesPhpFilesInSourceDirectory(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            file_put_contents(sprintf('%s/src/User.php', $tempDir), '<?php class User {}');
            file_put_contents(sprintf('%s/src/Post.php', $tempDir), '<?php class Post {}');

            $indexer = new Indexer($this->database, $tempDir, ['src']);
            $stats = $indexer->run();

            self::assertGreaterThan(0, $stats->getFilesScanned());
            self::assertGreaterThan(0, $stats->getSymbolsEmitted());
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testRunSkipsExcludedDirectories(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            mkdir(sprintf('%s/vendor', $tempDir), 0777, true);

            file_put_contents(sprintf('%s/src/Valid.php', $tempDir), '<?php class Valid {}');
            file_put_contents(sprintf('%s/vendor/External.php', $tempDir), '<?php class External {}');

            $indexer = new Indexer($this->database, $tempDir, ['src'], ['php'], ['vendor']);
            $stats = $indexer->run();

            self::assertGreaterThan(0, $stats->getFilesScanned());
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testRunReturnsEmptyStatsWhenNoFilesFound(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);

            $indexer = new Indexer($this->database, $tempDir, ['src']);
            $stats = $indexer->run();

            self::assertSame(0, $stats->getFilesScanned());
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testRunHandlesMultipleSourceDirectories(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            mkdir(sprintf('%s/app', $tempDir), 0777, true);

            file_put_contents(sprintf('%s/src/Service.php', $tempDir), '<?php class Service {}');
            file_put_contents(sprintf('%s/app/Controller.php', $tempDir), '<?php class Controller {}');

            $indexer = new Indexer($this->database, $tempDir, ['src', 'app']);
            $stats = $indexer->run();

            self::assertGreaterThanOrEqual(2, $stats->getFilesScanned());
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testRunRecordsDurationInStats(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            file_put_contents(sprintf('%s/src/Test.php', $tempDir), '<?php class Test {}');

            $indexer = new Indexer($this->database, $tempDir, ['src']);
            $stats = $indexer->run();

            self::assertGreaterThan(0, $stats->getDurationSeconds());
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testRunResolvesEdgesAfterProcessing(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);

            file_put_contents(
                sprintf('%s/src/Service.php', $tempDir),
                '<?php class Service { public function execute() { new User(); } }',
            );
            file_put_contents(sprintf('%s/src/User.php', $tempDir), '<?php class User {}');

            $indexer = new Indexer($this->database, $tempDir, ['src']);
            $stats = $indexer->run();

            self::assertGreaterThan(0, $stats->getSymbolsEmitted());
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    private function createTempDir(): string
    {
        $randomId = random_int(100000, 999999);
        $tempDir = sprintf('%s/codegraph_test_%d', sys_get_temp_dir(), $randomId);
        mkdir($tempDir);
        return $tempDir;
    }

    private function cleanupTempDir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                rmdir($fileinfo->getRealPath());
            } else {
                unlink($fileinfo->getRealPath());
            }
        }

        rmdir($path);
    }
}
