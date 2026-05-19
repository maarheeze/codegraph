<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Extraction;

use FilesystemIterator;
use Maarheeze\CodeGraph\Extraction\FileDiscoverer;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function file_put_contents;
use function is_dir;
use function is_file;
use function mkdir;
use function rmdir;
use function sprintf;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class FileDiscovererTest extends TestCase
{
    public function testDiscoveryFindsPhpFiles(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            mkdir(sprintf('%s/app', $tempDir), 0777, true);
            file_put_contents(sprintf('%s/src/User.php', $tempDir), '<?php class User {}');
            file_put_contents(sprintf('%s/app/Controller.php', $tempDir), '<?php class Controller {}');

            $discoverer = new FileDiscoverer($tempDir, ['src', 'app']);
            $files = $discoverer->discover();

            self::assertCount(2, $files);
            self::assertStringContainsString('User.php', $files[0]);
            self::assertStringContainsString('Controller.php', $files[1]);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testDiscoveryExcludesSpecifiedDirs(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            mkdir(sprintf('%s/vendor', $tempDir), 0777, true);
            file_put_contents(sprintf('%s/src/User.php', $tempDir), '<?php class User {}');
            file_put_contents(sprintf('%s/vendor/Package.php', $tempDir), '<?php class Package {}');

            $discoverer = new FileDiscoverer($tempDir, ['src', 'vendor'], ['php'], ['vendor']);
            $files = $discoverer->discover();

            self::assertCount(1, $files);
            self::assertStringContainsString('User.php', $files[0]);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testDiscoveryHandlesNestedDirectories(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src/Models', $tempDir), 0777, true);
            mkdir(sprintf('%s/src/Controllers', $tempDir), 0777, true);
            file_put_contents(sprintf('%s/src/Models/User.php', $tempDir), '<?php class User {}');
            file_put_contents(
                sprintf('%s/src/Controllers/UserController.php', $tempDir),
                '<?php class UserController {}',
            );

            $discoverer = new FileDiscoverer($tempDir, ['src']);
            $files = $discoverer->discover();

            self::assertCount(2, $files);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testDiscoveryReturnsEmptyWhenPathDoesNotExist(): void
    {
        $tempDir = $this->createTempDir();

        try {
            $discoverer = new FileDiscoverer($tempDir, ['nonexistent']);
            $files = $discoverer->discover();

            self::assertCount(0, $files);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testDiscoveryFiltersExtensions(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            file_put_contents(sprintf('%s/src/User.php', $tempDir), '<?php class User {}');
            file_put_contents(sprintf('%s/src/config.json', $tempDir), '{}');
            file_put_contents(sprintf('%s/src/README.md', $tempDir), '# Test');

            $discoverer = new FileDiscoverer($tempDir, ['src'], ['php']);
            $files = $discoverer->discover();

            self::assertCount(1, $files);
            self::assertStringContainsString('User.php', $files[0]);
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function testDiscoveryReturnsRelativePaths(): void
    {
        $tempDir = $this->createTempDir();

        try {
            mkdir(sprintf('%s/src', $tempDir), 0777, true);
            file_put_contents(sprintf('%s/src/User.php', $tempDir), '<?php class User {}');

            $discoverer = new FileDiscoverer($tempDir, ['src']);
            $files = $discoverer->discover();

            self::assertCount(1, $files);
            self::assertTrue(str_contains($files[0], 'User.php'));
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
