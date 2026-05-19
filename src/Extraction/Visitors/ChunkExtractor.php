<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Extraction\Visitors;

use Maarheeze\CodeGraph\Values\Chunk;
use Maarheeze\CodeGraph\Values\Symbol;

use function array_slice;
use function count;
use function explode;
use function implode;

use const PHP_EOL;

final readonly class ChunkExtractor
{
    public function __construct(
        private string $sourceContents,
        private string $relativeFilePath,
    ) {
    }

    /**
     * @param array<int, Symbol> $symbols
     * @return array<int, Chunk>
     */
    public function extract(array $symbols): array
    {
        $chunks = [];
        $lines = explode(PHP_EOL, $this->sourceContents);

        foreach ($symbols as $symbol) {
            $chunk = $this->createChunk($symbol, $lines);

            if ($chunk !== null) {
                $chunks[] = $chunk;
            }
        }

        return $chunks;
    }

    /**
     * @param array<int, string> $lines
     */
    private function createChunk(Symbol $symbol, array $lines): ?Chunk
    {
        $startIdx = $symbol->startLine - 1;
        $endIdx = $symbol->endLine - 1;

        if ($startIdx < 0 || $startIdx >= count($lines)) {
            return null;
        }

        if ($endIdx >= count($lines)) {
            $endIdx = count($lines) - 1;
        }

        $sliced = array_slice($lines, $startIdx, $endIdx - $startIdx + 1);
        $body = implode(PHP_EOL, $sliced);

        return new Chunk(
            $symbol->fullyQualifiedName,
            $symbol->kind,
            $this->relativeFilePath,
            $symbol->startLine,
            $symbol->endLine,
            $body,
        );
    }
}
