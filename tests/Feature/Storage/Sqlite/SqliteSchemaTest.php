<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Storage\Sqlite;

use Maarheeze\CodeGraph\Storage\Sqlite\SqliteSchema;
use Maarheeze\CodeGraph\Tests\Feature\FeatureTestCase;
use PDO;

final class SqliteSchemaTest extends FeatureTestCase
{
    public function testMigrateCreatesFilesTable(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);
        $schema->migrate();

        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='files'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        self::assertNotFalse($result);
        self::assertSame('files', $result['name']);
    }

    public function testMigrateCreatesSymbolsTable(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);
        $schema->migrate();

        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='symbols'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        self::assertNotFalse($result);
        self::assertSame('symbols', $result['name']);
    }

    public function testMigrateCreatesEdgesTable(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);
        $schema->migrate();

        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='edges'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        self::assertNotFalse($result);
        self::assertSame('edges', $result['name']);
    }

    public function testMigrateCreatesChunksTable(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);
        $schema->migrate();

        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='chunks'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        self::assertNotFalse($result);
        self::assertSame('chunks', $result['name']);
    }

    public function testMigrateCreatesFtsTable(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);
        $schema->migrate();

        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='chunks_fts'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        self::assertNotFalse($result);
        self::assertSame('chunks_fts', $result['name']);
    }

    public function testMigrateCreatesSchemaVersionTable(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);
        $schema->migrate();

        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='schema_version'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        self::assertNotFalse($result);
        self::assertSame('schema_version', $result['name']);
    }

    public function testMigrateCreatesSymbolsNameIndex(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);
        $schema->migrate();

        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND name='idx_symbols_name'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        self::assertNotFalse($result);
        self::assertSame('idx_symbols_name', $result['name']);
    }

    public function testMigrateCreatesSymbolsFileIndex(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);
        $schema->migrate();

        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND name='idx_symbols_file'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        self::assertNotFalse($result);
        self::assertSame('idx_symbols_file', $result['name']);
    }

    public function testMigrateCreatesEdgesSrcIndex(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);
        $schema->migrate();

        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND name='idx_edges_src'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        self::assertNotFalse($result);
        self::assertSame('idx_edges_src', $result['name']);
    }

    public function testMigrateCreatesEdgesDstIndex(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);
        $schema->migrate();

        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND name='idx_edges_dst'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        self::assertNotFalse($result);
        self::assertSame('idx_edges_dst', $result['name']);
    }

    public function testMigrateCreatesEdgesFileIndex(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);
        $schema->migrate();

        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND name='idx_edges_file'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        self::assertNotFalse($result);
        self::assertSame('idx_edges_file', $result['name']);
    }

    public function testMigrateRecordsSchemaVersion(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);
        $schema->migrate();

        $stmt = $pdo->query('SELECT version FROM schema_version');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        self::assertNotFalse($result);
        self::assertSame('1', (string) $result['version']);
    }

    public function testMigrateIsIdempotent(): void
    {
        $pdo = $this->createInMemoryDatabase();
        $schema = new SqliteSchema($pdo);

        $schema->migrate();
        $stmt1 = $pdo->query("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table'");
        $result1 = $stmt1->fetch(PDO::FETCH_ASSOC);
        $tableCount1 = (int) $result1['count'];

        $schema->migrate();
        $stmt2 = $pdo->query("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table'");
        $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        $tableCount2 = (int) $result2['count'];

        self::assertSame($tableCount1, $tableCount2);
    }

    private function createInMemoryDatabase(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}
