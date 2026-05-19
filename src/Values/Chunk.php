<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Values;

final readonly class Chunk
{
    public function __construct(
        public string $fullyQualifiedName,
        public string $kind,
        public string $file,
        public int $startLine,
        public int $endLine,
        public string $body,
    ) {
    }
}
