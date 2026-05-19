<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Commands;

use Maarheeze\CodeGraph\CodeGraph;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function array_key_exists;
use function array_merge;
use function count;
use function getcwd;
use function hash_file;
use function is_dir;
use function sleep;
use function sprintf;
use function str_ends_with;

#[AsCommand('watch', 'Watch for file changes and automatically reindex')]
final class WatchCommand extends Command
{
    /** @var array<string, string> */
    private array $fileHashes = [];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cwd = getcwd();

        if ($cwd === false) {
            $output->writeln('<error>Error: Unable to determine current working directory</error>');

            return Command::FAILURE;
        }

        $codeGraph = CodeGraph::forProject($cwd);
        $output->writeln('<info>Watching for changes... (press Ctrl+C to stop)</info>');

        $this->buildInitialHashes($cwd);
        $this->watch($cwd, $codeGraph, $output);

        return Command::SUCCESS;
    }

    private function watch(string $cwd, CodeGraph $codeGraph, OutputInterface $output): void
    {
        /** @phpstan-ignore-next-line while.alwaysTrue */
        while (true) {
            sleep(1);
            $changed = $this->detectChanges($cwd);

            if ($changed === []) {
                continue;
            }

            $output->writeln(sprintf(
                '<fg=yellow>%d file(s) changed, reindexing...</>',
                count($changed),
            ));

            $stats = $codeGraph->index();

            $output->writeln(sprintf(
                '<fg=green>✓</> Indexed <fg=cyan>%d</> files, <fg=cyan>%d</> symbols, <fg=cyan>%d</> edges',
                $stats->getFilesChanged(),
                $stats->getSymbolsEmitted(),
                $stats->getEdgesEmitted(),
            ));
        }
    }

    private function buildInitialHashes(string $cwd): void
    {
        $codeGraph = CodeGraph::forProject($cwd);
        $scanPaths = $codeGraph->getScanPaths();

        foreach ($scanPaths as $path) {
            $fullPath = sprintf('%s/%s', $cwd, $path);
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
     * @return array<int, string>
     */
    private function detectChanges(string $cwd): array
    {
        $codeGraph = CodeGraph::forProject($cwd);
        $currentHashes = $this->scanCurrentHashes($cwd, $codeGraph->getScanPaths());

        $changed = $this->detectFileChanges($currentHashes);
        $changed = array_merge($changed, $this->detectDeletedFiles($currentHashes));

        $this->fileHashes = $currentHashes;

        return $changed;
    }

    /**
     * @param array<int, string> $scanPaths
     * @return array<string, string>
     */
    private function scanCurrentHashes(string $cwd, array $scanPaths): array
    {
        $currentHashes = [];

        foreach ($scanPaths as $path) {
            $fullPath = sprintf('%s/%s', $cwd, $path);

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
