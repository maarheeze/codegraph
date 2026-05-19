<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Extraction;

use Maarheeze\CodeGraph\Storage\Sqlite\SqliteGraph;
use Maarheeze\CodeGraph\Values\IndexStats;
use Webmozart\Assert\Assert;

final readonly class FileChangeAnalyzer
{
    public function __construct(
        private SqliteGraph $graph,
    ) {
    }

    /**
     * @param array<string, mixed> $fileData
     */
    public function hasChanged(array $fileData, IndexStats $stats): bool
    {
        $relPath = $fileData['relPath'];
        Assert::string($relPath);
        $meta = $this->graph->getFileMeta($relPath);

        if ($meta === null) {
            $stats->incrementFilesChanged();
            return true;
        }

        if ($meta['mtime'] === $fileData['mtime'] && $meta['size'] === $fileData['size']) {
            $stats->incrementFilesSkipped();
            return false;
        }

        if ($meta['sha256'] === $fileData['sha256']) {
            $mtime = $fileData['mtime'];
            Assert::integer($mtime);
            $this->graph->touchFileMtime($relPath, $mtime);
            $stats->incrementFilesSkipped();
            return false;
        }

        $stats->incrementFilesChanged();

        return true;
    }
}
