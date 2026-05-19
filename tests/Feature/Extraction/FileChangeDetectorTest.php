<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Extraction;

use FilesystemIterator;
use Maarheeze\CodeGraph\Extraction\FileChangeDetector;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function file_put_contents;
use function hash;
use function is_dir;
use function is_file;
use function mkdir;
use function rmdir;
use function sprintf;
use function strlen;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class FileChangeDetectorTest extends TestCase
{
    public function testAnalyzeReturnsFileDataForValidFile(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            $filePath = sprintf('%s/src/User.php', $tempDir);
            file_put_contents($filePath, '<?php class User {}');

            $detector = new FileChangeDetector($tempDir);
            $result = $detector->analyze($filePath);

            self::assertIsArray($result);
            self::assertArrayHasKey('relPath', $result);
            self::assertArrayHasKey('contents', $result);
            self::assertArrayHasKey('sha256', $result);
            self::assertArrayHasKey('size', $result);
            self::assertArrayHasKey('mtime', $result);
            self::assertSame('src/User.php', $result['relPath']);
            self::assertSame('<?php class User {}', $result['contents']);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testAnalyzeReturnsNullForNonexistentFile(): void
    {
        $tempDir = $this->createTempDir();

        try {
            $detector = new FileChangeDetector($tempDir);
            $result = $detector->analyze(sprintf('%s/nonexistent.php', $tempDir));

            self::assertNull($result);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testAnalyzeComputesCorrectSha256(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            $filePath = sprintf('%s/src/User.php', $tempDir);
            $content = '<?php class User {}';
            file_put_contents($filePath, $content);

            $detector = new FileChangeDetector($tempDir);
            $result = $detector->analyze($filePath);

            self::assertIsArray($result);
            $expectedSha256 = hash('sha256', $content);
            self::assertSame($expectedSha256, $result['sha256']);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testAnalyzeReturnsCorrectSize(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            $filePath = sprintf('%s/src/User.php', $tempDir);
            $content = '<?php class User {}';
            file_put_contents($filePath, $content);

            $detector = new FileChangeDetector($tempDir);
            $result = $detector->analyze($filePath);

            self::assertIsArray($result);
            self::assertSame(strlen($content), $result['size']);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testAnalyzeReturnsCorrectModificationTime(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            $filePath = sprintf('%s/src/User.php', $tempDir);
            file_put_contents($filePath, '<?php class User {}');

            $detector = new FileChangeDetector($tempDir);
            $result = $detector->analyze($filePath);

            self::assertIsArray($result);
            self::assertIsInt($result['mtime']);
            self::assertGreaterThan(0, $result['mtime']);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testAnalyzeNormalizesPathSeparators(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src/Models', $tempDir), 0777, true);
            $filePath = sprintf('%s/src/Models/User.php', $tempDir);
            file_put_contents($filePath, '<?php class User {}');

            $detector = new FileChangeDetector($tempDir);
            $result = $detector->analyze($filePath);

            self::assertIsArray($result);
            self::assertStringNotContainsString('\\', $result['relPath']);
            self::assertSame('src/Models/User.php', $result['relPath']);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    private function createTempDir(): string
    {
        $tempDir = tempnam(sys_get_temp_dir(), 'codegraph_test_');
        if (is_file($tempDir)) {
            unlink($tempDir);
        }
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
