<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Extraction;

use function file_exists;
use function file_get_contents;
use function filemtime;
use function filesize;
use function hash;
use function str_replace;
use function strlen;
use function substr;

use const DIRECTORY_SEPARATOR;

final readonly class FileChangeDetector
{
    public function __construct(
        private string $rootPath,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function analyze(string $filePath): ?array
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $relPathRaw = substr($filePath, strlen($this->rootPath) + 1);
        $relPath = str_replace(DIRECTORY_SEPARATOR, '/', $relPathRaw);

        $size = filesize($filePath);
        $mtime = filemtime($filePath);

        if ($size === false || $mtime === false) {
            return null;
        }

        $contents = file_get_contents($filePath);

        if ($contents === false) {
            return null;
        }

        $sha256 = hash('sha256', $contents);

        return [
            'relPath' => $relPath,
            'contents' => $contents,
            'sha256' => $sha256,
            'size' => $size,
            'mtime' => $mtime,
        ];
    }
}
