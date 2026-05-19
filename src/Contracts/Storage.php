<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Contracts;

use Maarheeze\CodeGraph\Values\Chunk;
use Maarheeze\CodeGraph\Values\Edge;
use Maarheeze\CodeGraph\Values\Symbol;

interface Storage
{
    /**
     * @return array<int, string>
     */
    public function blastRadius(string $fqn, int $depth = 3): array;

    public function countChunks(): int;

    public function countEdges(): int;

    public function countFiles(): int;

    public function countSymbols(): int;

    /**
     * @return array<int, Symbol>
     */
    public function findByName(string $name): array;

    /**
     * @return array<int, Edge>
     */
    public function findEdgesFrom(string $fqn): array;

    /**
     * @return array<int, Edge>
     */
    public function findEdgesTo(string $fqn): array;

    /**
     * @return ?array{sha256: string, size: int, mtime: int}
     */
    public function getFileMeta(string $path): ?array;

    public function migrate(): void;

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
    ): void;

    public function resolveEdges(): void;

    /**
     * @return array<int, Chunk>
     */
    public function searchChunks(string $query): array;

    public function touchFileMtime(string $path, int $mtime): void;
}
