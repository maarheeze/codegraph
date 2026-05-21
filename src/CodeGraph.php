<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph;

use Maarheeze\CodeGraph\Contracts\Plugin;
use Maarheeze\CodeGraph\Contracts\Storage;
use Maarheeze\CodeGraph\Extraction\Indexer;
use Maarheeze\CodeGraph\Plugin\PluginRegistry;
use Maarheeze\CodeGraph\Storage\Sqlite\SqliteGraph;
use Maarheeze\CodeGraph\Values\IndexStats;

use function is_dir;
use function sprintf;

final class CodeGraph
{
    private ?SqliteGraph $graph = null;
    private PluginRegistry $pluginRegistry;

    /**
     * @param array<int, string> $scanPaths
     * @param array<int, string> $excludes
     */
    public function __construct(
        private readonly string $rootPath,
        private readonly string $dbPath,
        private readonly array $scanPaths = ['src', 'app'],
        private readonly array $excludes = ['vendor', 'node_modules', 'storage'],
        ?PluginRegistry $pluginRegistry = null,
    ) {
        $this->pluginRegistry = $pluginRegistry ?? new PluginRegistry();
    }

    public static function forProject(string $rootPath = '.'): self
    {
        $defaultPaths = ['src', 'app'];
        $scanPaths = [];

        foreach ($defaultPaths as $path) {
            if (is_dir(sprintf('%s/%s', $rootPath, $path))) {
                $scanPaths[] = $path;
            }
        }

        if ($scanPaths === []) {
            $scanPaths = $defaultPaths;
        }

        return new self(
            $rootPath,
            Paths::databasePath($rootPath),
            $scanPaths,
        );
    }

    /**
     * @return array<int, string>
     */
    public function getScanPaths(): array
    {
        return $this->scanPaths;
    }

    public function getStorage(): Storage
    {
        return $this->graph();
    }

    public function getPluginRegistry(): PluginRegistry
    {
        return $this->pluginRegistry;
    }

    public function index(): IndexStats
    {
        $indexer = new Indexer(
            $this->graph(),
            $this->rootPath,
            $this->scanPaths,
            ['php'],
            $this->excludes,
            $this->pluginRegistry,
        );

        return $indexer->run();
    }

    public function registerPlugin(Plugin $plugin): void
    {
        $this->pluginRegistry->register($plugin);
    }

    /**
     * @return array{symbols: int, edges: int, chunks: int, files: int}
     */
    public function stats(): array
    {
        $graph = $this->graph();

        return [
            'symbols' => $graph->countSymbols(),
            'edges' => $graph->countEdges(),
            'chunks' => $graph->countChunks(),
            'files' => $graph->countFiles(),
        ];
    }

    private function graph(): SqliteGraph
    {
        if ($this->graph === null) {
            $this->graph = new SqliteGraph($this->dbPath);
            $this->graph->migrate();
        }

        return $this->graph;
    }
}
