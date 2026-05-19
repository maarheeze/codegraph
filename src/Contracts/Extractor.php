<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Contracts;

use Maarheeze\CodeGraph\Values\Chunk;
use Maarheeze\CodeGraph\Values\Edge;
use Maarheeze\CodeGraph\Values\Symbol;

interface Extractor
{
    /**
     * @return array<int, Chunk>
     */
    public function chunks(): array;

    /**
     * @return array<int, Edge>
     */
    public function edges(): array;

    /**
     * @return array<int, Symbol>
     */
    public function symbols(): array;
}
