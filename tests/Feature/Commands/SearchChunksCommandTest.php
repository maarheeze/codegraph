<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Commands;

use Maarheeze\CodeGraph\Commands\SearchChunksCommand;
use Maarheeze\CodeGraph\Storage\Sqlite\SqliteGraph;
use Maarheeze\CodeGraph\Values\Chunk;
use Symfony\Component\Console\Command\Command;

final class SearchChunksCommandTest extends CommandTestCase
{
    public function testOutputsEmptyArrayWhenNoMatch(): void
    {
        $tester = $this->runCommand(new SearchChunksCommand(), ['query' => 'nonexistent']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame([], $this->decodeJson($tester));
    }

    public function testOutputsMatchingChunksAsJson(): void
    {
        $this->seed(static function (SqliteGraph $graph): void {
            $chunk = new Chunk(
                'App\Models\User',
                'class',
                'app/Models/User.php',
                1,
                50,
                'class User { public function getName() { return $this->name; } }',
            );

            $graph->recordFile('app/Models/User.php', 'hash123', 1000, 1234567890, [], [], [$chunk]);
        });

        $tester = $this->runCommand(new SearchChunksCommand(), ['query' => 'getName']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $data = $this->decodeJson($tester);
        self::assertSame('App\Models\User', $data[0]['fqn']);
        self::assertStringContainsString('getName', $data[0]['body']);
    }
}
