<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Extraction;

use Maarheeze\CodeGraph\Contracts\FileVisitor;
use Maarheeze\CodeGraph\Values\Edge;
use Maarheeze\CodeGraph\Values\Symbol;
use PhpParser\NodeVisitorAbstract;

abstract class BaseAstVisitor extends NodeVisitorAbstract implements FileVisitor
{
    /** @var array<int, Symbol> */
    protected array $symbolsList = [];

    /** @var array<int, Edge> */
    protected array $edgesList = [];

    public function __construct(
        protected readonly string $relativeFilePath,
        protected readonly string $sourceContents,
    ) {
    }

    /**
     * @return array<int, Edge>
     */
    public function edges(): array
    {
        return $this->edgesList;
    }

    /**
     * @return array<int, Symbol>
     */
    public function symbols(): array
    {
        return $this->symbolsList;
    }
}
