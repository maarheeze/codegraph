<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Values;

final readonly class Edge
{
    public function __construct(
        public string $kind,
        public string $sourceFullyQualifiedName,
        public string $destinationFullyQualifiedName,
        public string $file,
        public int $line,
        public ?string $metadata = null,
    ) {
    }
}
