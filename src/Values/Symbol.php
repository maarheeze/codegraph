<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Values;

final readonly class Symbol
{
    public function __construct(
        public string $kind,
        public string $name,
        public string $fullyQualifiedName,
        public ?string $parentFullyQualifiedName,
        public string $file,
        public int $startLine,
        public int $endLine,
        public string $visibility,
        public bool $isStatic,
        public bool $isAbstract,
        public string $signature,
        public ?string $docblock,
    ) {
    }
}
