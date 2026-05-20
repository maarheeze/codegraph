<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Services;

use PDO;
use PDOException;
use PDOStatement;
use Throwable;
use Webmozart\Assert\Assert;

use function count;
use function file_exists;
use function filesize;
use function floor;
use function log;
use function max;
use function min;
use function round;
use function sprintf;

final readonly class StatusService
{
    public function __construct(
        private string $databasePath,
    ) {
    }

    /**
     * @return array{lines: array<int, string>, error: ?string}
     */
    public function run(): array
    {
        try {
            $pdo = $this->pdo();
            $stats = [
                'symbols' => $this->countRecords($pdo, 'symbols'),
                'edges' => $this->countRecords($pdo, 'edges'),
                'chunks' => $this->countRecords($pdo, 'chunks'),
                'files' => $this->countRecords($pdo, 'files'),
            ];

            $lines = [
                '<info>CodeGraph Status:</info>',
                sprintf('  Symbols: <fg=cyan>%d</>', $stats['symbols']),
                sprintf('  Edges:   <fg=cyan>%d</>', $stats['edges']),
                sprintf('  Chunks:  <fg=cyan>%d</>', $stats['chunks']),
                sprintf('  Files:   <fg=cyan>%d</>', $stats['files']),
                sprintf('  DB path: <fg=cyan>%s</>', $this->databasePath),
            ];

            if (file_exists($this->databasePath)) {
                $dbSize = filesize($this->databasePath);
                if ($dbSize !== false) {
                    $lines[] = sprintf('  DB size: <fg=cyan>%s</>', $this->formatBytes($dbSize));
                }
            }

            return [
                'lines' => $lines,
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'lines' => [],
                'error' => sprintf('Failed to read status: %s', $e->getMessage()),
            ];
        }
    }

    /**
     * @return array{symbols: int, edges: int, chunks: int, files: int}
     */
    public function getOverallStats(): array
    {
        $pdo = $this->pdo();

        return [
            'symbols' => $this->countRecords($pdo, 'symbols'),
            'edges' => $this->countRecords($pdo, 'edges'),
            'chunks' => $this->countRecords($pdo, 'chunks'),
            'files' => $this->countRecords($pdo, 'files'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getAllEdgeKinds(): array
    {
        $pdo = $this->pdo();
        $stmt = $pdo->prepare('SELECT DISTINCT kind FROM edges ORDER BY kind');
        Assert::notNull($stmt);
        $stmt->execute();

        $kinds = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            Assert::isArray($row);
            $kind = $row['kind'];
            Assert::string($kind);
            $kinds[] = $kind;
        }

        return $kinds;
    }

    /**
     * @return array<int, array{kind: string, src_fqn: string, dst_fqn: string, file: string, line: int}>
     */
    public function getEdgesByKind(string $kind): array
    {
        $pdo = $this->pdo();
        $stmt = $pdo->prepare(
            'SELECT kind, src_fqn, dst_fqn, file, line FROM edges WHERE kind = ? ORDER BY file, line',
        );
        Assert::notNull($stmt);
        $stmt->execute([$kind]);

        return $this->hydrateEdges($stmt);
    }

    /**
     * @return array<int, array{kind: string, src_fqn: string, dst_fqn: string, file: string, line: int}>
     */
    public function getSampleEdges(string $kind, int $limit = 5): array
    {
        $pdo = $this->pdo();
        $stmt = $pdo->prepare(
            'SELECT kind, src_fqn, dst_fqn, file, line FROM edges WHERE kind = ? ORDER BY file, line LIMIT ?',
        );
        Assert::notNull($stmt);
        $stmt->execute([$kind, $limit]);

        return $this->hydrateEdges($stmt);
    }

    /**
     * @param PDOStatement $stmt
     * @return array<int, array{kind: string, src_fqn: string, dst_fqn: string, file: string, line: int}>
     */
    private function hydrateEdges(PDOStatement $stmt): array
    {
        $edges = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            Assert::isArray($row);
            $kind = $row['kind'];
            Assert::string($kind);
            $srcFqn = $row['src_fqn'];
            Assert::string($srcFqn);
            $dstFqn = $row['dst_fqn'];
            Assert::string($dstFqn);
            $file = $row['file'];
            Assert::string($file);
            $line = $row['line'];
            Assert::integer($line);
            $edges[] = [
                'kind' => $kind,
                'src_fqn' => $srcFqn,
                'dst_fqn' => $dstFqn,
                'file' => $file,
                'line' => $line,
            ];
        }

        return $edges;
    }

    private function countRecords(PDO $pdo, string $table): int
    {
        $stmt = $pdo->query(sprintf('SELECT COUNT(*) as count FROM %s', $table));
        if ($stmt === false) {
            throw new PDOException(sprintf('Failed to query count from %s', $table));
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        Assert::isArray($row);
        $count = $row['count'];
        Assert::integer($count, sprintf('Count from %s must be an integer', $table));

        return $count;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = (int) min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return sprintf('%s %s', round($bytes, 2), $units[$pow]);
    }

    private function pdo(): PDO
    {
        try {
            return new PDO(sprintf('sqlite:%s', $this->databasePath));
        } catch (PDOException $e) {
            throw new PDOException(
                sprintf('Failed to open CodeGraph database at %s: %s', $this->databasePath, $e->getMessage()),
                0,
                $e,
            );
        }
    }
}
