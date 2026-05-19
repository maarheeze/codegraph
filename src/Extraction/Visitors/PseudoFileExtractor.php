<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Extraction\Visitors;

use Maarheeze\CodeGraph\Values\Symbol;

use function basename;
use function count;
use function explode;
use function str_contains;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

final readonly class PseudoFileExtractor
{
    public function __construct(
        private string $relativeFilePath,
        private string $sourceContents,
    ) {
    }

    public function extract(): Symbol
    {
        $kind = match (true) {
            str_contains($this->relativeFilePath, 'migrations' . DIRECTORY_SEPARATOR) => 'migration',
            str_contains($this->relativeFilePath, 'config' . DIRECTORY_SEPARATOR) => 'config',
            str_contains($this->relativeFilePath, 'routes' . DIRECTORY_SEPARATOR) => 'routes',
            default => 'file',
        };

        $basename = basename($this->relativeFilePath, '.php');
        $lineCount = count(explode(PHP_EOL, $this->sourceContents));

        return new Symbol(
            $kind,
            $basename,
            $this->relativeFilePath,
            null,
            $this->relativeFilePath,
            1,
            $lineCount,
            '',
            false,
            false,
            '',
            null,
        );
    }
}
