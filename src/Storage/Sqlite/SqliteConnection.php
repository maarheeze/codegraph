<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Storage\Sqlite;

use PDO;

use function sprintf;

final readonly class SqliteConnection
{
    public function __construct(
        private string $dbPath,
    ) {
    }

    public function connect(): PDO
    {
        $pdo = new PDO(sprintf('sqlite:%s', $this->dbPath));
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA foreign_keys=ON');

        return $pdo;
    }
}
