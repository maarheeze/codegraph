<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Extraction;

use Maarheeze\CodeGraph\Contracts\FileVisitor;

final class ExtractorRegistry
{
    /**
     * @var array<int, FileVisitor>
     */
    private array $visitors = [];

    public function register(FileVisitor $visitor): void
    {
        $this->visitors[] = $visitor;
    }

    /**
     * @return array<int, FileVisitor>
     */
    public function all(): array
    {
        return $this->visitors;
    }
}
