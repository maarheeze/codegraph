<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Commands;

use Maarheeze\CodeGraph\Commands\SearchCommand;
use Maarheeze\CodeGraph\Storage\Sqlite\SqliteGraph;
use Maarheeze\CodeGraph\Values\Symbol;
use Symfony\Component\Console\Command\Command;

final class SearchCommandTest extends CommandTestCase
{
    public function testOutputsEmptyArrayWhenNoSymbols(): void
    {
        $tester = $this->runCommand(new SearchCommand(), ['name' => 'NonExistent']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame([], $this->decodeJson($tester));
    }

    public function testOutputsMatchingSymbolsAsJson(): void
    {
        $this->seed(static function (SqliteGraph $graph): void {
            $symbol = new Symbol(
                'class',
                'UserService',
                'App\Services\UserService',
                null,
                'app/Services/UserService.php',
                10,
                100,
                'public',
                false,
                false,
                'class UserService',
                null,
            );

            $graph->recordFile('app/Services/UserService.php', 'hash123', 1000, 1234567890, [$symbol], [], []);
        });

        $tester = $this->runCommand(new SearchCommand(), ['name' => 'UserService']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $data = $this->decodeJson($tester);
        self::assertSame('UserService', $data[0]['name']);
        self::assertSame('App\Services\UserService', $data[0]['fqn']);
    }
}
