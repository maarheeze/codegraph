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
use function str_repeat;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class FileChangeDetectorEdgeCasesTest extends TestCase
{
    public function testAnalyzeReturnNullWhenFileDoesNotExist(): void
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

    public function testAnalyzeReturnHashAndMetadataForValidFile(): void
    {
        $tempDir = $this->createTempDir();

        try {
            $filePath = sprintf('%s/test.php', $tempDir);
            $content = '<?php echo "test";';
            file_put_contents($filePath, $content);

            $detector = new FileChangeDetector($tempDir);
            $result = $detector->analyze($filePath);

            self::assertIsArray($result);
            self::assertArrayHasKey('relPath', $result);
            self::assertArrayHasKey('sha256', $result);
            self::assertArrayHasKey('size', $result);
            self::assertArrayHasKey('mtime', $result);
            self::assertArrayHasKey('contents', $result);
            self::assertSame('test.php', $result['relPath']);
            self::assertSame($content, $result['contents']);
            self::assertSame(hash('sha256', $content), $result['sha256']);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testAnalyzePreservesDirectoryStructureInRelativePath(): void
    {
        $tempDir = $this->createTempDir();

        try {
            $nestedPath = sprintf('%s/src/Models', $tempDir);
            mkdir($nestedPath, 0777, true);
            $filePath = sprintf('%s/User.php', $nestedPath);
            file_put_contents($filePath, '<?php class User {}');

            $detector = new FileChangeDetector($tempDir);
            $result = $detector->analyze($filePath);

            self::assertIsArray($result);
            self::assertSame('src/Models/User.php', $result['relPath']);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testAnalyzeHandlesDifferentFileSizes(): void
    {
        $tempDir = $this->createTempDir();

        try {
            $smallFile = sprintf('%s/small.php', $tempDir);
            file_put_contents($smallFile, 'a');

            $largeContent = str_repeat('x', 10000);
            $largeFile = sprintf('%s/large.php', $tempDir);
            file_put_contents($largeFile, $largeContent);

            $detector = new FileChangeDetector($tempDir);

            $smallResult = $detector->analyze($smallFile);
            $largeResult = $detector->analyze($largeFile);

            self::assertIsArray($smallResult);
            self::assertIsArray($largeResult);
            self::assertSame(1, $smallResult['size']);
            self::assertSame(10000, $largeResult['size']);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testAnalyzeDifferentiatesDifferentHashesForDifferentContent(): void
    {
        $tempDir = $this->createTempDir();

        try {
            $file1 = sprintf('%s/file1.php', $tempDir);
            file_put_contents($file1, '<?php echo "one";');

            $file2 = sprintf('%s/file2.php', $tempDir);
            file_put_contents($file2, '<?php echo "two";');

            $detector = new FileChangeDetector($tempDir);

            $result1 = $detector->analyze($file1);
            $result2 = $detector->analyze($file2);

            self::assertIsArray($result1);
            self::assertIsArray($result2);
            self::assertNotSame($result1['sha256'], $result2['sha256']);
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
