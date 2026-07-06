<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Services;

use Maarheeze\CodeGraph\Contracts\Storage;
use Maarheeze\CodeGraph\Values\Edge;

use function count;
use function sprintf;
use function substr;

final readonly class QueryService
{
    public function __construct(
        private Storage $storage,
    ) {
    }

    /**
     * @return array{query: string, depth: int, affected_count: int, affected_symbols: array<int, string>}
     */
    public function blastRadius(string $fqn, int $depth = 3): array
    {
        $affected = $this->storage->blastRadius($fqn, $depth);

        return [
            'query' => $fqn,
            'depth' => $depth,
            'affected_count' => count($affected),
            'affected_symbols' => $affected,
        ];
    }

    /**
     * @return array<int, array{kind: string, caller: string, callee: string, file: string, line: int}>
     */
    public function callees(string $fqn): array
    {
        return $this->mapEdges($this->storage->findEdgesFrom($fqn));
    }

    /**
     * @return array<int, array{kind: string, caller: string, callee: string, file: string, line: int}>
     */
    public function callers(string $fqn): array
    {
        return $this->mapEdges($this->storage->findEdgesTo($fqn));
    }

    /**
     * @return array<int, array{kind: string, name: string, fqn: string, file: string, line: int, signature: string}>
     */
    public function search(string $name): array
    {
        $result = [];

        foreach ($this->storage->findByName($name) as $symbol) {
            $result[] = [
                'kind' => $symbol->kind,
                'name' => $symbol->name,
                'fqn' => $symbol->fullyQualifiedName,
                'file' => $symbol->file,
                'line' => $symbol->startLine,
                'signature' => $symbol->signature,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{fqn: string, kind: string, file: string, lines: string, body: string}>
     */
    public function searchChunks(string $query): array
    {
        $result = [];

        foreach ($this->storage->searchChunks($query) as $chunk) {
            $result[] = [
                'fqn' => $chunk->fullyQualifiedName,
                'kind' => $chunk->kind,
                'file' => $chunk->file,
                'lines' => sprintf('%d-%d', $chunk->startLine, $chunk->endLine),
                'body' => substr($chunk->body, 0, 500),
            ];
        }

        return $result;
    }

    /**
     * @param array<int, Edge> $edges
     * @return array<int, array{kind: string, caller: string, callee: string, file: string, line: int}>
     */
    private function mapEdges(array $edges): array
    {
        $result = [];

        foreach ($edges as $edge) {
            $result[] = [
                'kind' => $edge->kind,
                'caller' => $edge->sourceFullyQualifiedName,
                'callee' => $edge->destinationFullyQualifiedName,
                'file' => $edge->file,
                'line' => $edge->line,
            ];
        }

        return $result;
    }
}
