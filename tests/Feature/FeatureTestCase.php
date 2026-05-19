<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature;

use Maarheeze\CodeGraph\CodeGraph;
use Maarheeze\CodeGraph\Storage\Sqlite\SqliteGraph;
use Maarheeze\CodeGraph\Tests\TestCase;

abstract class FeatureTestCase extends TestCase
{
    protected SqliteGraph $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createTempDatabase();
    }

    protected function createTempDatabase(): SqliteGraph
    {
        $database = new SqliteGraph(':memory:');
        $database->migrate();

        return $database;
    }

    protected function withTempCodeGraph(callable $callback): void
    {
        $codeGraph = new CodeGraph(__DIR__ . '/../..', ':memory:', ['src']);
        $codeGraph->index();

        $callback($codeGraph);
    }
}
