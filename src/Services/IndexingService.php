<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Services;

use Maarheeze\CodeGraph\CodeGraph;
use Maarheeze\CodeGraph\Contracts\Plugin;
use Maarheeze\CodeGraph\Plugin\PluginRegistry;

use function array_filter;
use function array_values;
use function is_dir;
use function sprintf;

final readonly class IndexingService
{
    public function __construct(
        private PluginRegistry $pluginRegistry = new PluginRegistry(),
    ) {
    }

    /**
     * @param array<int, string> $scanPaths
     * @param array<int, string> $excludes
     * @return array{lines: array<int, string>, error: ?string}
     */
    public function run(
        string $projectRoot,
        string $databasePath,
        array $scanPaths,
        array $excludes,
    ): array {
        $validation = $this->validateScanPaths($projectRoot, $scanPaths);

        if ($validation['error'] !== null) {
            return [
                'lines' => [],
                'error' => $validation['error'],
            ];
        }

        $lines = [];
        $lines[] = '<comment>Scanning directories:</comment>';
        foreach ($validation['paths'] as $path) {
            $lines[] = sprintf('  <comment>-</comment> %s', $path);
        }
        $lines[] = '<info>Starting index...</info>';

        $this->pluginRegistry->beforeIndex();

        $codeGraph = new CodeGraph(
            $projectRoot,
            $databasePath,
            $validation['paths'],
            $excludes,
            $this->pluginRegistry,
        );

        $stats = $codeGraph->index();

        $this->pluginRegistry->afterExtraction();
        $this->pluginRegistry->beforeResolution();
        $this->pluginRegistry->afterResolution();
        $this->pluginRegistry->afterIndex();

        $lines[] = 'Index complete:';
        $lines[] = sprintf('  Files scanned:   %d', $stats->getFilesScanned());
        $lines[] = sprintf('  Files changed:   %d', $stats->getFilesChanged());
        $lines[] = sprintf('  Files skipped:   %d', $stats->getFilesSkipped());
        $lines[] = sprintf('  Files failed:    %d', $stats->getFilesFailed());
        $lines[] = sprintf('  Symbols emitted: %d', $stats->getSymbolsEmitted());
        $lines[] = sprintf('  Edges emitted:   %d', $stats->getEdgesEmitted());
        $lines[] = sprintf('  Chunks emitted:  %d', $stats->getChunksEmitted());
        $lines[] = sprintf('  Duration:        %.2fs', $stats->getDurationSeconds());

        if ($stats->getErrors() !== []) {
            $lines[] = 'Errors encountered:';
            foreach ($stats->getErrors() as $error) {
                $lines[] = sprintf('  - %s', $error);
            }
        }

        return [
            'lines' => $lines,
            'error' => null,
        ];
    }

    public function registerPlugin(Plugin $plugin): void
    {
        $this->pluginRegistry->register($plugin);
    }

    /**
     * @param array<int, string> $scanPaths
     * @return array{paths: array<int, string>, error: ?string}
     */
    private function validateScanPaths(string $projectRoot, array $scanPaths): array
    {
        $validated = array_filter($scanPaths, static function (string $path) use ($projectRoot): bool {
            return is_dir(sprintf('%s/%s', $projectRoot, $path));
        });
        $validated = array_values($validated);

        if ($validated === []) {
            return [
                'paths' => [],
                'error' => 'No scan directories found. Create src/ or app/ or specify --path',
            ];
        }

        return [
            'paths' => $validated,
            'error' => null,
        ];
    }
}
