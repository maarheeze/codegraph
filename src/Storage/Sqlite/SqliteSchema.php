<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Storage\Sqlite;

use PDO;
use PDOStatement;
use Webmozart\Assert\Assert;

final readonly class SqliteSchema
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function migrate(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS files (
                path TEXT NOT NULL PRIMARY KEY,
                sha256 TEXT NOT NULL,
                size INTEGER NOT NULL,
                mtime INTEGER NOT NULL,
                indexed_at INTEGER NOT NULL
            )',
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS symbols (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kind TEXT NOT NULL,
                name TEXT NOT NULL,
                fqn TEXT NOT NULL UNIQUE,
                parent_fqn TEXT,
                file TEXT NOT NULL,
                start_line INTEGER NOT NULL,
                end_line INTEGER NOT NULL,
                visibility TEXT NOT NULL DEFAULT "",
                is_static INTEGER NOT NULL DEFAULT 0,
                is_abstract INTEGER NOT NULL DEFAULT 0,
                signature TEXT NOT NULL DEFAULT "",
                docblock TEXT
            )',
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS edges (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kind TEXT NOT NULL,
                src_fqn TEXT NOT NULL,
                dst_fqn TEXT NOT NULL,
                file TEXT NOT NULL,
                line INTEGER NOT NULL,
                metadata TEXT
            )',
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS chunks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                fqn TEXT NOT NULL,
                kind TEXT NOT NULL,
                file TEXT NOT NULL,
                start_line INTEGER NOT NULL,
                end_line INTEGER NOT NULL,
                body TEXT NOT NULL
            )',
        );

        $this->pdo->exec(
            'CREATE VIRTUAL TABLE IF NOT EXISTS chunks_fts
                USING fts5(fqn, kind, file, body, content=chunks, content_rowid=id)',
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_version (version INTEGER NOT NULL)',
        );

        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_symbols_name ON symbols(name)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_symbols_file ON symbols(file)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_edges_src ON edges(src_fqn)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_edges_dst ON edges(dst_fqn)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_edges_file ON edges(file)');

        $stmt = $this->pdo->query('SELECT COUNT(*) as count FROM schema_version');
        Assert::isInstanceOf($stmt, PDOStatement::class);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            $this->pdo->exec('INSERT INTO schema_version (version) VALUES (1)');
            return;
        }
        Assert::isArray($row);
        $count = $row['count'];
        Assert::integer($count);
        if ($count === 0) {
            $this->pdo->exec('INSERT INTO schema_version (version) VALUES (1)');
        }
    }
}
