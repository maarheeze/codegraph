<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Storage\Sqlite;

use Maarheeze\CodeGraph\Contracts\Storage;
use Maarheeze\CodeGraph\Values\Chunk;
use Maarheeze\CodeGraph\Values\Edge;
use Maarheeze\CodeGraph\Values\Symbol;
use PDO;
use PDOStatement;
use RuntimeException;
use Webmozart\Assert\Assert;

use function array_key_exists;
use function array_keys;
use function count;
use function sprintf;
use function time;

final class SqliteGraph implements Storage
{
    private ?PDO $pdo = null;
    private ?SqliteConnection $connection = null;
    private ?SqliteSchema $schema = null;

    public function __construct(
        private readonly string $dbPath,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function blastRadius(string $fqn, int $depth = 3): array
    {
        $visited = [];
        $frontier = [$fqn];

        for ($d = 0; $d < $depth; ++$d) {
            if (count($frontier) === 0) {
                break;
            }

            $frontier = $this->expandFrontier($frontier, $visited);
        }

        return array_keys($visited);
    }

    /**
     * @param array<int, string> $frontier
     * @param array<string, bool> $visited
     * @return array<int, string>
     */
    private function expandFrontier(array $frontier, array &$visited): array
    {
        $nextFrontier = [];

        foreach ($frontier as $current) {
            if (array_key_exists($current, $visited)) {
                continue;
            }

            $visited[$current] = true;
            $callers = $this->findCallers($current);

            foreach ($callers as $caller) {
                $nextFrontier[] = $caller;
            }
        }

        return $nextFrontier;
    }

    /**
     * @return array<int, string>
     */
    private function findCallers(string $fqn): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT src_fqn FROM edges WHERE dst_fqn = ?',
        );
        Assert::isInstanceOf($stmt, PDOStatement::class);
        $stmt->execute([$fqn]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $callers = [];

        foreach ($rows as $row) {
            Assert::isArray($row);
            $sourceFullyQualifiedName = $row['src_fqn'];
            Assert::string($sourceFullyQualifiedName);
            $callers[] = $sourceFullyQualifiedName;
        }

        return $callers;
    }

    public function countChunks(): int
    {
        return $this->count('chunks');
    }

    public function countEdges(): int
    {
        return $this->count('edges');
    }

    public function countFiles(): int
    {
        return $this->count('files');
    }

    public function countSymbols(): int
    {
        return $this->count('symbols');
    }

    /**
     * @return array<int, Symbol>
     */
    public function findByName(string $name): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT kind, name, fqn, parent_fqn, file, start_line, end_line, visibility,' .
            ' is_static, is_abstract, signature, docblock FROM symbols' .
            ' WHERE name LIKE ? OR fqn LIKE ? LIMIT 50',
        );
        $stmt->execute([sprintf('%%%s%%', $name), sprintf('%%%s%%', $name)]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $symbols = [];
        foreach ($rows as $row) {
            Assert::isArray($row);
            $symbols[] = $this->hydrateSymbol($row);
        }

        return $symbols;
    }

    /**
     * @return array<int, Edge>
     */
    public function findEdgesFrom(string $fqn): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT kind, src_fqn, dst_fqn, file, line, metadata FROM edges WHERE src_fqn = ?',
        );
        $stmt->execute([$fqn]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $edges = [];
        foreach ($rows as $row) {
            Assert::isArray($row);
            $edges[] = $this->hydrateEdge($row);
        }

        return $edges;
    }

    /**
     * @return array<int, Edge>
     */
    public function findEdgesTo(string $fqn): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT kind, src_fqn, dst_fqn, file, line, metadata FROM edges WHERE dst_fqn = ?',
        );
        $stmt->execute([$fqn]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $edges = [];
        foreach ($rows as $row) {
            Assert::isArray($row);
            $edges[] = $this->hydrateEdge($row);
        }

        return $edges;
    }

    /**
     * @return ?array{sha256: string, size: int, mtime: int}
     */
    public function getFileMeta(string $path): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT sha256, size, mtime FROM files WHERE path = ?',
        );
        Assert::isInstanceOf($stmt, PDOStatement::class);
        $stmt->execute([$path]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        Assert::isArray($row);
        $sha256 = $row['sha256'];
        Assert::string($sha256);
        $size = $row['size'];
        Assert::integer($size);
        $mtime = $row['mtime'];
        Assert::integer($mtime);

        return [
            'sha256' => $sha256,
            'size' => $size,
            'mtime' => $mtime,
        ];
    }

    public function migrate(): void
    {
        $this->schema()->migrate();
    }

    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->connection()->connect();
        }

        return $this->pdo;
    }

    /**
     * @param array<int, Symbol> $symbols
     * @param array<int, Edge> $edges
     * @param array<int, Chunk> $chunks
     */
    public function recordFile(
        string $path,
        string $sha256,
        int $size,
        int $mtime,
        array $symbols,
        array $edges,
        array $chunks,
    ): void {
        $pdo = $this->pdo();

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('DELETE FROM chunks WHERE file = ?');
            $stmt->execute([$path]);
            $stmt = $pdo->prepare('DELETE FROM edges WHERE file = ?');
            $stmt->execute([$path]);
            $stmt = $pdo->prepare('DELETE FROM symbols WHERE file = ?');
            $stmt->execute([$path]);

            $stmt = $pdo->prepare(
                'INSERT OR REPLACE INTO files (path, sha256, size, mtime, indexed_at) VALUES (?, ?, ?, ?, ?)',
            );
            $stmt->execute([$path, $sha256, $size, $mtime, time()]);

            foreach ($symbols as $symbol) {
                $stmt = $pdo->prepare(
                    'INSERT INTO symbols (kind, name, fqn, parent_fqn, file, start_line,' .
                    ' end_line, visibility, is_static, is_abstract, signature, docblock)' .
                    ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                );
                $stmt->execute([
                    $symbol->kind,
                    $symbol->name,
                    $symbol->fullyQualifiedName,
                    $symbol->parentFullyQualifiedName,
                    $symbol->file,
                    $symbol->startLine,
                    $symbol->endLine,
                    $symbol->visibility,
                    $symbol->isStatic ? 1 : 0,
                    $symbol->isAbstract ? 1 : 0,
                    $symbol->signature,
                    $symbol->docblock,
                ]);
            }

            foreach ($edges as $edge) {
                $stmt = $pdo->prepare(
                    'INSERT INTO edges (kind, src_fqn, dst_fqn, file, line, metadata) VALUES (?, ?, ?, ?, ?, ?)',
                );
                $stmt->execute([
                    $edge->kind,
                    $edge->sourceFullyQualifiedName,
                    $edge->destinationFullyQualifiedName,
                    $edge->file,
                    $edge->line,
                    $edge->metadata,
                ]);
            }

            foreach ($chunks as $chunk) {
                $stmt = $pdo->prepare(
                    'INSERT INTO chunks (fqn, kind, file, start_line, end_line, body) VALUES (?, ?, ?, ?, ?, ?)',
                );
                $stmt->execute([
                    $chunk->fullyQualifiedName,
                    $chunk->kind,
                    $chunk->file,
                    $chunk->startLine,
                    $chunk->endLine,
                    $chunk->body,
                ]);

                $stmt = $pdo->prepare(
                    'INSERT INTO chunks_fts (fqn, kind, file, body, rowid) VALUES' .
                    ' (?, ?, ?, ?, (SELECT id FROM chunks WHERE fqn = ? AND file = ?' .
                    ' AND start_line = ?))',
                );
                $stmt->execute([
                    $chunk->fullyQualifiedName,
                    $chunk->kind,
                    $chunk->file,
                    $chunk->body,
                    $chunk->fullyQualifiedName,
                    $chunk->file,
                    $chunk->startLine,
                ]);
            }

            $pdo->commit();
        } catch (RuntimeException $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function resolveEdges(): void
    {
        $this->pdo()->exec(
            'UPDATE edges SET dst_fqn = (
                SELECT fqn FROM symbols WHERE name = edges.dst_fqn LIMIT 1
            )
            WHERE dst_fqn NOT IN (SELECT fqn FROM symbols)
              AND EXISTS (SELECT 1 FROM symbols WHERE name = edges.dst_fqn)',
        );
    }

    /**
     * @return array<int, Chunk>
     */
    public function searchChunks(string $query): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT chunks.id, chunks.fqn, chunks.kind, chunks.file, chunks.start_line, chunks.end_line, chunks.body
             FROM chunks_fts
             JOIN chunks ON chunks.id = chunks_fts.rowid
             WHERE chunks_fts MATCH ?',
        );
        $stmt->execute([$query]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $chunks = [];
        foreach ($rows as $row) {
            Assert::isArray($row);
            $chunks[] = $this->hydrateChunk($row);
        }

        return $chunks;
    }

    public function touchFileMtime(string $path, int $mtime): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE files SET mtime = ? WHERE path = ?',
        );
        $stmt->execute([$mtime, $path]);
    }

    private function count(string $table): int
    {
        Assert::inArray($table, ['symbols', 'edges', 'chunks', 'files']);
        $stmt = $this->pdo()->query(sprintf('SELECT COUNT(*) as count FROM %s', $table));
        Assert::isInstanceOf($stmt, PDOStatement::class);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return 0;
        }
        Assert::isArray($row);
        $count = $row['count'];
        Assert::integer($count);

        return $count;
    }

    private function connection(): SqliteConnection
    {
        if ($this->connection === null) {
            $this->connection = new SqliteConnection($this->dbPath);
        }

        return $this->connection;
    }

    private function schema(): SqliteSchema
    {
        if ($this->schema === null) {
            $this->schema = new SqliteSchema($this->pdo());
        }

        return $this->schema;
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private function extractString(array $row, string $key): string
    {
        $value = $row[$key];
        Assert::string($value);
        return $value;
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private function extractInteger(array $row, string $key): int
    {
        $value = $row[$key];
        Assert::integer($value);
        return $value;
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private function extractNullableString(array $row, string $key): ?string
    {
        $value = $row[$key];
        Assert::nullOrString($value);
        return $value;
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private function hydrateChunk(array $row): Chunk
    {
        return new Chunk(
            $this->extractString($row, 'fqn'),
            $this->extractString($row, 'kind'),
            $this->extractString($row, 'file'),
            $this->extractInteger($row, 'start_line'),
            $this->extractInteger($row, 'end_line'),
            $this->extractString($row, 'body'),
        );
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private function hydrateEdge(array $row): Edge
    {
        return new Edge(
            $this->extractString($row, 'kind'),
            $this->extractString($row, 'src_fqn'),
            $this->extractString($row, 'dst_fqn'),
            $this->extractString($row, 'file'),
            $this->extractInteger($row, 'line'),
            $this->extractNullableString($row, 'metadata'),
        );
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private function hydrateSymbol(array $row): Symbol
    {
        return new Symbol(
            $this->extractString($row, 'kind'),
            $this->extractString($row, 'name'),
            $this->extractString($row, 'fqn'),
            $this->extractNullableString($row, 'parent_fqn'),
            $this->extractString($row, 'file'),
            $this->extractInteger($row, 'start_line'),
            $this->extractInteger($row, 'end_line'),
            $this->extractString($row, 'visibility'),
            (bool) $this->extractInteger($row, 'is_static'),
            (bool) $this->extractInteger($row, 'is_abstract'),
            $this->extractString($row, 'signature'),
            $this->extractNullableString($row, 'docblock'),
        );
    }
}
