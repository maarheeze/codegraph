<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Services;

use Maarheeze\CodeGraph\CodeGraph;
use Maarheeze\CodeGraph\Values\IndexStats;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_key_exists;
use function array_merge;
use function count;
use function hash_file;
use function is_dir;
use function sleep;
use function sprintf;
use function str_ends_with;

final class WatchService
{
    /** @var array<string, string> */
    private array $fileHashes = [];

    public function __construct(
        private readonly string $projectRoot,
        private readonly string $databasePath,
    ) {
    }

    /**
     * @param callable(int): void $onChangesDetected
     * @param callable(IndexStats): void $onIndexed
     */
    public function run(callable $onChangesDetected, callable $onIndexed): never
    {
        $codeGraph = new CodeGraph(
            $this->projectRoot,
            $this->databasePath,
        );

        $this->buildInitialHashes($codeGraph->getScanPaths());

        while (true) {
            sleep(1);
            $changed = $this->detectChanges($this->projectRoot, $codeGraph->getScanPaths());

            if ($changed === []) {
                continue;
            }

            ($onChangesDetected)(count($changed));

            $stats = $codeGraph->index();

            ($onIndexed)($stats);
        }
    }

    /**
     * @param array<int, string> $scanPaths
     */
    private function buildInitialHashes(array $scanPaths): void
    {
        foreach ($scanPaths as $path) {
            $fullPath = sprintf('%s/%s', $this->projectRoot, $path);
            $this->hashDirectory($fullPath);
        }
    }

    private function hashDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveDirectoryIterator($path);
        $recursiveIterator = new RecursiveIteratorIterator($iterator);

        foreach ($recursiveIterator as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }

            if ($file->isDir() || !$file->isFile()) {
                continue;
            }

            $filePath = $file->getPathname();

            if (!str_ends_with($filePath, '.php')) {
                continue;
            }

            $hash = hash_file('sha256', $filePath);
            if ($hash !== false) {
                $this->fileHashes[$filePath] = $hash;
            }
        }
    }

    /**
     * @param array<int, string> $scanPaths
     * @return array<int, string>
     */
    private function detectChanges(string $projectRoot, array $scanPaths): array
    {
        $currentHashes = $this->scanCurrentHashes($projectRoot, $scanPaths);

        $changed = $this->detectFileChanges($currentHashes);
        $changed = array_merge($changed, $this->detectDeletedFiles($currentHashes));

        $this->fileHashes = $currentHashes;

        return $changed;
    }

    /**
     * @param array<int, string> $scanPaths
     * @return array<string, string>
     */
    private function scanCurrentHashes(string $projectRoot, array $scanPaths): array
    {
        $currentHashes = [];

        foreach ($scanPaths as $path) {
            $fullPath = sprintf('%s/%s', $projectRoot, $path);

            if (!is_dir($fullPath)) {
                continue;
            }

            $iterator = new RecursiveDirectoryIterator($fullPath);
            $recursiveIterator = new RecursiveIteratorIterator($iterator);

            foreach ($recursiveIterator as $file) {
                if (!$file instanceof SplFileInfo || $file->isDir()) {
                    continue;
                }

                if (!$file->isFile()) {
                    continue;
                }

                $filePath = $file->getPathname();

                if (!str_ends_with($filePath, '.php')) {
                    continue;
                }

                $hash = hash_file('sha256', $filePath);

                if ($hash !== false) {
                    $currentHashes[$filePath] = $hash;
                }
            }
        }

        return $currentHashes;
    }

    /**
     * @param array<string, string> $currentHashes
     * @return array<int, string>
     */
    private function detectFileChanges(array $currentHashes): array
    {
        $changed = [];

        foreach ($currentHashes as $filePath => $hash) {
            if (!array_key_exists($filePath, $this->fileHashes)) {
                $changed[] = $filePath;
            } elseif ($this->fileHashes[$filePath] !== $hash) {
                $changed[] = $filePath;
            }
        }

        return $changed;
    }

    /**
     * @param array<string, string> $currentHashes
     * @return array<int, string>
     */
    private function detectDeletedFiles(array $currentHashes): array
    {
        $deleted = [];

        foreach ($this->fileHashes as $filePath => $hash) {
            if (!array_key_exists($filePath, $currentHashes)) {
                $deleted[] = $filePath;
            }
        }

        return $deleted;
    }
}
