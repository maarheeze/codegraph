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
use function strlen;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const PHP_EOL;

final class FileChangeDetectorAdditionalTest extends TestCase
{
    public function testAnalyzeReturnsCorrectRelativePath(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src/Models', $tempDir), 0777, true);
            $filePath = sprintf('%s/src/Models/User.php', $tempDir);
            file_put_contents($filePath, '<?php class User {}');

            $detector = new FileChangeDetector($tempDir);
            $result = $detector->analyze($filePath);

            self::assertIsArray($result);
            self::assertSame('src/Models/User.php', $result['relPath']);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testAnalyzeReturnsMtimeAsInteger(): void
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
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testAnalyzeReturnsSizeAsInteger(): void
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
            self::assertIsInt($result['size']);
            self::assertSame(strlen($content), $result['size']);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testAnalyzeReturnsContents(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            $filePath = sprintf('%s/src/User.php', $tempDir);
            $content = '<?php class User { public function getName() {} }';
            file_put_contents($filePath, $content);

            $detector = new FileChangeDetector($tempDir);
            $result = $detector->analyze($filePath);

            self::assertIsArray($result);
            self::assertSame($content, $result['contents']);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testAnalyzeReturnsSha256Hash(): void
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
            self::assertSame(hash('sha256', $content), $result['sha256']);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testAnalyzeHandlesLargeFiles(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            $filePath = sprintf('%s/src/LargeFile.php', $tempDir);
            $content = '<?php ' . str_repeat('// comment' . PHP_EOL, 1000);
            file_put_contents($filePath, $content);

            $detector = new FileChangeDetector($tempDir);
            $result = $detector->analyze($filePath);

            self::assertIsArray($result);
            self::assertGreaterThan(0, $result['size']);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testAnalyzeHandlesEmptyFile(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            $filePath = sprintf('%s/src/Empty.php', $tempDir);
            file_put_contents($filePath, '');

            $detector = new FileChangeDetector($tempDir);
            $result = $detector->analyze($filePath);

            self::assertIsArray($result);
            self::assertSame(0, $result['size']);
            self::assertSame('', $result['contents']);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testAnalyzeHandlesSpecialCharactersInContent(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            $filePath = sprintf('%s/src/Special.php', $tempDir);
            $content = '<?php $str = "Special chars: \n\t\r ñ é ü";';
            file_put_contents($filePath, $content);

            $detector = new FileChangeDetector($tempDir);
            $result = $detector->analyze($filePath);

            self::assertIsArray($result);
            self::assertSame($content, $result['contents']);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testAnalyzeReturnsNullWhenFileDoesNotExist(): void
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

    public function testAnalyzeReturnsCorrectSizeForDifferentFileSizes(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            $filePath = sprintf('%s/src/SmallFile.php', $tempDir);
            $content = '<?php class X {}';
            file_put_contents($filePath, $content);

            $detector = new FileChangeDetector($tempDir);
            $result = $detector->analyze($filePath);

            self::assertIsArray($result);
            self::assertSame(strlen($content), $result['size']);
            self::assertSame($content, $result['contents']);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testAnalyzeReturnsValidHashForDifferentContents(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            $filePath1 = sprintf('%s/src/File1.php', $tempDir);
            $filePath2 = sprintf('%s/src/File2.php', $tempDir);

            $content1 = '<?php class User {}';
            $content2 = '<?php class Post {}';

            file_put_contents($filePath1, $content1);
            file_put_contents($filePath2, $content2);

            $detector = new FileChangeDetector($tempDir);
            $result1 = $detector->analyze($filePath1);
            $result2 = $detector->analyze($filePath2);

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
