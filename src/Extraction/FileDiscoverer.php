<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Extraction;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function is_dir;
use function sprintf;
use function str_contains;
use function str_ends_with;

final readonly class FileDiscoverer
{
    /**
     * @param array<int, string> $paths
     * @param array<int, string> $extensions
     * @param array<int, string> $excludes
     */
    public function __construct(
        private string $rootPath,
        private array $paths = ['app', 'src'],
        private array $extensions = ['php'],
        private array $excludes = ['vendor', 'node_modules', 'storage'],
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function discover(): array
    {
        $files = [];

        foreach ($this->paths as $path) {
            $fullPath = sprintf('%s/%s', $this->rootPath, $path);

            if (!is_dir($fullPath)) {
                continue;
            }

            $iterator = new RecursiveDirectoryIterator($fullPath);
            $recursiveIterator = new RecursiveIteratorIterator($iterator);

            foreach ($recursiveIterator as $file) {
                if (!$file instanceof SplFileInfo || $file->isDir()) {
                    continue;
                }

                $filePath = $file->getPathname();

                if ($this->matchesExtension($filePath) && !$this->shouldExclude($filePath)) {
                    $files[] = $filePath;
                }
            }
        }

        return $files;
    }

    private function matchesExtension(string $filePath): bool
    {
        foreach ($this->extensions as $extension) {
            if (str_ends_with($filePath, sprintf('.%s', $extension))) {
                return true;
            }
        }

        return false;
    }

    private function shouldExclude(string $filePath): bool
    {
        foreach ($this->excludes as $exclude) {
            if (str_contains($filePath, $exclude)) {
                return true;
            }
        }

        return false;
    }
}
