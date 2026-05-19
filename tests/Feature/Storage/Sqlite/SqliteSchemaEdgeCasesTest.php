<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Storage\Sqlite;

use Maarheeze\CodeGraph\Storage\Sqlite\SqliteSchema;
use Maarheeze\CodeGraph\Tests\Feature\FeatureTestCase;
use PDO;

use function array_column;
use function sprintf;

final class SqliteSchemaEdgeCasesTest extends FeatureTestCase
{
    public function testMigrateIsIdempotentOnMultipleCalls(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);

        $schema->migrate();
        $schema->migrate();
        $schema->migrate();

        $stmt = $pdo->query('SELECT COUNT(*) as count FROM sqlite_master WHERE type="table"');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $tableCount = (int) $result['count'];

        self::assertGreaterThan(0, $tableCount);
    }

    public function testMigrateCreatesAllTablesInSingleCall(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);
        $schema->migrate();

        $expectedTables = ['files', 'symbols', 'edges', 'chunks', 'chunks_fts', 'schema_version'];

        foreach ($expectedTables as $tableName) {
            $stmt = $pdo->query(sprintf("SELECT name FROM sqlite_master WHERE type='table' AND name='%s'", $tableName));
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            self::assertNotFalse($result, sprintf('Table %s should exist', $tableName));
        }
    }

    public function testMigrateCreatesAllIndexes(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);
        $schema->migrate();

        $expectedIndexes = [
            'idx_symbols_name',
            'idx_symbols_file',
            'idx_edges_src',
            'idx_edges_dst',
            'idx_edges_file',
        ];

        foreach ($expectedIndexes as $indexName) {
            $stmt = $pdo->query(sprintf("SELECT name FROM sqlite_master WHERE type='index' AND name='%s'", $indexName));
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            self::assertNotFalse($result, sprintf('Index %s should exist', $indexName));
        }
    }

    public function testMigrateRecordsSchemaVersionOnlyOnce(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);

        $schema->migrate();
        $stmt1 = $pdo->query('SELECT COUNT(*) as count FROM schema_version');
        $result1 = $stmt1->fetch(PDO::FETCH_ASSOC);
        $count1 = (int) $result1['count'];

        $schema->migrate();
        $stmt2 = $pdo->query('SELECT COUNT(*) as count FROM schema_version');
        $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        $count2 = (int) $result2['count'];

        self::assertSame(1, $count1);
        self::assertSame(1, $count2);
    }

    public function testTablesHaveExpectedColumns(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);
        $schema->migrate();

        $stmt = $pdo->query('PRAGMA table_info(symbols)');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        self::assertNotEmpty($columns);
        $columnNames = array_column($columns, 'name');
        self::assertContains('kind', $columnNames);
        self::assertContains('name', $columnNames);
        self::assertContains('fqn', $columnNames);
    }

    public function testEdgesTableHasExpectedColumns(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);
        $schema->migrate();

        $stmt = $pdo->query('PRAGMA table_info(edges)');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $columnNames = array_column($columns, 'name');
        self::assertContains('kind', $columnNames);
        self::assertContains('src_fqn', $columnNames);
        self::assertContains('dst_fqn', $columnNames);
    }

    public function testMigrateHandlesEmptySchemaVersion(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);

        $schema->migrate();
        $pdo->exec('DELETE FROM schema_version');

        $schema->migrate();

        $stmt = $pdo->query('SELECT COUNT(*) as count FROM schema_version');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = (int) $result['count'];

        self::assertSame(1, $count);
    }

    private function createInMemoryDatabase(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}
