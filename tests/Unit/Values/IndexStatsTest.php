<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Unit\Values;

use Maarheeze\CodeGraph\Tests\TestCase;
use Maarheeze\CodeGraph\Values\IndexStats;

final class IndexStatsTest extends TestCase
{
    public function testIndexStatsInitializesToZero(): void
    {
        $stats = new IndexStats();

        self::assertSame(0, $stats->getFilesScanned());
        self::assertSame(0, $stats->getFilesChanged());
        self::assertSame(0, $stats->getFilesSkipped());
        self::assertSame(0, $stats->getFilesFailed());
        self::assertSame(0, $stats->getSymbolsEmitted());
        self::assertSame(0, $stats->getEdgesEmitted());
        self::assertSame(0, $stats->getChunksEmitted());
        self::assertSame(0.0, $stats->getDurationSeconds());
        self::assertSame([], $stats->getErrors());
    }

    public function testIncrementFilesScanned(): void
    {
        $stats = new IndexStats();

        $stats->incrementFilesScanned();
        self::assertSame(1, $stats->getFilesScanned());

        $stats->incrementFilesScanned();
        self::assertSame(2, $stats->getFilesScanned());
    }

    public function testIncrementFilesChanged(): void
    {
        $stats = new IndexStats();

        $stats->incrementFilesChanged();
        $stats->incrementFilesChanged();
        self::assertSame(2, $stats->getFilesChanged());
    }

    public function testIncrementFilesSkipped(): void
    {
        $stats = new IndexStats();

        $stats->incrementFilesSkipped();
        self::assertSame(1, $stats->getFilesSkipped());
    }

    public function testIncrementFilesFailed(): void
    {
        $stats = new IndexStats();

        $stats->incrementFilesFailed();
        $stats->incrementFilesFailed();
        $stats->incrementFilesFailed();
        self::assertSame(3, $stats->getFilesFailed());
    }

    public function testAddSymbols(): void
    {
        $stats = new IndexStats();

        $stats->addSymbols(10);
        self::assertSame(10, $stats->getSymbolsEmitted());

        $stats->addSymbols(5);
        self::assertSame(15, $stats->getSymbolsEmitted());
    }

    public function testAddEdges(): void
    {
        $stats = new IndexStats();

        $stats->addEdges(25);
        self::assertSame(25, $stats->getEdgesEmitted());

        $stats->addEdges(10);
        self::assertSame(35, $stats->getEdgesEmitted());
    }

    public function testAddChunks(): void
    {
        $stats = new IndexStats();

        $stats->addChunks(10);
        self::assertSame(10, $stats->getChunksEmitted());

        $stats->addChunks(5);
        self::assertSame(15, $stats->getChunksEmitted());
    }

    public function testSetDuration(): void
    {
        $stats = new IndexStats();

        $stats->setDuration(1.5);
        self::assertSame(1.5, $stats->getDurationSeconds());

        $stats->setDuration(2.75);
        self::assertSame(2.75, $stats->getDurationSeconds());
    }

    public function testAddError(): void
    {
        $stats = new IndexStats();

        $stats->addError('File not found');
        self::assertSame(['File not found'], $stats->getErrors());

        $stats->addError('Invalid syntax');
        self::assertSame(['File not found', 'Invalid syntax'], $stats->getErrors());
    }

    public function testCompleteIndexingScenario(): void
    {
        $stats = new IndexStats();

        $stats->incrementFilesScanned();
        $stats->incrementFilesScanned();
        $stats->incrementFilesScanned();
        $stats->incrementFilesChanged();
        $stats->incrementFilesChanged();
        $stats->incrementFilesSkipped();

        $stats->addSymbols(463);
        $stats->addEdges(1849);
        $stats->addChunks(463);

        $stats->setDuration(0.27);

        self::assertSame(3, $stats->getFilesScanned());
        self::assertSame(2, $stats->getFilesChanged());
        self::assertSame(1, $stats->getFilesSkipped());
        self::assertSame(0, $stats->getFilesFailed());
        self::assertSame(463, $stats->getSymbolsEmitted());
        self::assertSame(1849, $stats->getEdgesEmitted());
        self::assertSame(463, $stats->getChunksEmitted());
        self::assertSame(0.27, $stats->getDurationSeconds());
        self::assertSame([], $stats->getErrors());
    }
}
