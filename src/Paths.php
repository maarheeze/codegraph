<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph;

use function sprintf;

final class Paths
{
    public static function databasePath(string $projectRoot): string
    {
        return sprintf('%s/.codegraph/index.sqlite', $projectRoot);
    }

    public static function directoryPath(string $projectRoot): string
    {
        return sprintf('%s/.codegraph', $projectRoot);
    }
}
