<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Contracts;

use Maarheeze\CodeGraph\Values\Edge;
use Maarheeze\CodeGraph\Values\Symbol;

interface FileVisitor
{
    /**
     * @return array<int, Symbol>
     */
    public function symbols(): array;

    /**
     * @return array<int, Edge>
     */
    public function edges(): array;
}
