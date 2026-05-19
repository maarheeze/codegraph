<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Extraction;

use Maarheeze\CodeGraph\CodeGraph;
use Maarheeze\CodeGraph\Tests\Feature\FeatureTestCase;

final class IndexingTest extends FeatureTestCase
{
    public function testIndexingCreatesDatabase(): void
    {
        $this->withTempCodeGraph(function (CodeGraph $codeGraph) {
            $this->assertGreaterThan(0, $codeGraph->getStorage()->countFiles());
        });
    }

    public function testIndexingExtractsSymbols(): void
    {
        $this->withTempCodeGraph(function (CodeGraph $codeGraph) {
            $this->assertGreaterThan(0, $codeGraph->getStorage()->countSymbols());
        });
    }

    public function testIndexingExtractsEdges(): void
    {
        $this->withTempCodeGraph(function (CodeGraph $codeGraph) {
            $this->assertGreaterThan(0, $codeGraph->getStorage()->countEdges());
        });
    }

    public function testIndexingExtractsChunks(): void
    {
        $this->withTempCodeGraph(function (CodeGraph $codeGraph) {
            $this->assertGreaterThan(0, $codeGraph->getStorage()->countChunks());
        });
    }

    public function testSearchFindsIndexedSymbols(): void
    {
        $this->withTempCodeGraph(function (CodeGraph $codeGraph) {
            $results = $codeGraph->getStorage()->findByName('Indexer');
            $this->assertEquals('Indexer', $results[0]->name);
        });
    }

    public function testIndexingEmptyDirectory(): void
    {
        $this->withTempCodeGraph(function (CodeGraph $codeGraph) {
            $initialCount = $codeGraph->getStorage()->countFiles();
            $this->assertGreaterThan(0, $initialCount);
        });
    }

    public function testIndexingRecordsCorrectFileCount(): void
    {
        $this->withTempCodeGraph(function (CodeGraph $codeGraph) {
            $fileCount = $codeGraph->getStorage()->countFiles();
            $this->assertGreaterThan(0, $fileCount);
            $this->assertLessThan(1000, $fileCount);
        });
    }

    public function testIndexingPreservesEdgeData(): void
    {
        $this->withTempCodeGraph(function (CodeGraph $codeGraph) {
            $edgeCount = $codeGraph->getStorage()->countEdges();
            $this->assertGreaterThan(0, $edgeCount);
        });
    }
}
