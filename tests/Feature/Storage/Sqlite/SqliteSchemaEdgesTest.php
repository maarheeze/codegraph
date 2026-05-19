<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Storage\Sqlite;

use Maarheeze\CodeGraph\Storage\Sqlite\SqliteSchema;
use Maarheeze\CodeGraph\Tests\Feature\FeatureTestCase;
use PDO;

final class SqliteSchemaEdgesTest extends FeatureTestCase
{
    public function testMigrateCreatesAllTables(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $schema = new SqliteSchema($pdo);

        $schema->migrate();

        $tables = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type='table'",
        );

        $tableNames = [];
        foreach ($tables as $row) {
            $tableNames[] = $row['name'];
        }

        self::assertContains('files', $tableNames);
        self::assertContains('symbols', $tableNames);
        self::assertContains('edges', $tableNames);
        self::assertContains('chunks', $tableNames);
        self::assertContains('schema_version', $tableNames);
    }

    public function testMigrateCreatesAllIndexes(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $schema = new SqliteSchema($pdo);

        $schema->migrate();

        $indexes = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type='index' AND name LIKE 'idx_%'",
        );

        $indexNames = [];
        foreach ($indexes as $row) {
            $indexNames[] = $row['name'];
        }

        self::assertContains('idx_symbols_name', $indexNames);
        self::assertContains('idx_symbols_file', $indexNames);
        self::assertContains('idx_edges_src', $indexNames);
        self::assertContains('idx_edges_dst', $indexNames);
        self::assertContains('idx_edges_file', $indexNames);
    }

    public function testMigrateCreatesFtsTable(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $schema = new SqliteSchema($pdo);

        $schema->migrate();

        $tables = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='chunks_fts'",
        );

        $result = $tables->fetch();
        self::assertIsArray($result);
        self::assertSame('chunks_fts', $result['name']);
    }

    public function testMigrateInsertsSchemaVersionOnFirstRun(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $schema = new SqliteSchema($pdo);

        $schema->migrate();

        $version = $pdo->query('SELECT version FROM schema_version');
        $row = $version->fetch();

        self::assertIsArray($row);
        self::assertSame(1, (int) $row['version']);
    }

    public function testMigrateIdempotentWhenCalledMultipleTimes(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $schema = new SqliteSchema($pdo);

        $schema->migrate();
        $schema->migrate();

        $version = $pdo->query('SELECT COUNT(*) as count FROM schema_version');
        $row = $version->fetch();

        self::assertIsArray($row);
        self::assertSame(1, (int) $row['count']);
    }

    public function testMigrateSymbolsTableHasCorrectColumns(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $schema = new SqliteSchema($pdo);

        $schema->migrate();

        $columns = $pdo->query('PRAGMA table_info(symbols)');

        $columnNames = [];
        foreach ($columns as $row) {
            $columnNames[] = $row['name'];
        }

        self::assertContains('kind', $columnNames);
        self::assertContains('name', $columnNames);
        self::assertContains('fqn', $columnNames);
        self::assertContains('file', $columnNames);
        self::assertContains('start_line', $columnNames);
        self::assertContains('end_line', $columnNames);
    }

    public function testMigrateEdgesTableHasCorrectColumns(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $schema = new SqliteSchema($pdo);

        $schema->migrate();

        $columns = $pdo->query('PRAGMA table_info(edges)');

        $columnNames = [];
        foreach ($columns as $row) {
            $columnNames[] = $row['name'];
        }

        self::assertContains('kind', $columnNames);
        self::assertContains('src_fqn', $columnNames);
        self::assertContains('dst_fqn', $columnNames);
        self::assertContains('file', $columnNames);
    }
}
