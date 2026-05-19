<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Extraction;

use Maarheeze\CodeGraph\Storage\Sqlite\SqliteGraph;
use Maarheeze\CodeGraph\Values\IndexStats;
use PhpParser\ParserFactory;

use function is_file;
use function microtime;

final readonly class Indexer
{
    private FileDiscoverer $discoverer;
    private FileChangeDetector $changeDetector;
    private FileProcessor $processor;
    private FileChangeAnalyzer $changeAnalyzer;

    /**
     * @param array<int, string> $paths
     * @param array<int, string> $extensions
     * @param array<int, string> $excludes
     */
    public function __construct(
        private SqliteGraph $graph,
        string $rootPath,
        array $paths = ['app', 'src'],
        array $extensions = ['php'],
        array $excludes = ['node_modules', 'storage', 'vendor'],
    ) {
        $this->discoverer = new FileDiscoverer($rootPath, $paths, $extensions, $excludes);
        $this->changeDetector = new FileChangeDetector($rootPath);
        $this->processor = new FileProcessor($graph, (new ParserFactory())->createForHostVersion());
        $this->changeAnalyzer = new FileChangeAnalyzer($graph);
    }

    public function run(): IndexStats
    {
        $startTime = microtime(true);
        $stats = new IndexStats();

        $files = $this->discoverer->discover();

        if ($files === []) {
            return $stats;
        }

        foreach ($files as $filePath) {
            if (!is_file($filePath)) {
                continue;
            }

            $stats->incrementFilesScanned();

            $fileData = $this->changeDetector->analyze($filePath);
            if ($fileData === null) {
                $stats->incrementFilesFailed();
                continue;
            }

            if ($this->changeAnalyzer->hasChanged($fileData, $stats)) {
                $this->processor->process($fileData, $stats);
            }
        }

        $this->graph->resolveEdges();

        $duration = microtime(true) - $startTime;
        $stats->setDuration($duration);

        return $stats;
    }
}
