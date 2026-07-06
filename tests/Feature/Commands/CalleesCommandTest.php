<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Commands;

use Maarheeze\CodeGraph\Commands\CalleesCommand;
use Maarheeze\CodeGraph\Storage\Sqlite\SqliteGraph;
use Maarheeze\CodeGraph\Values\Edge;
use Symfony\Component\Console\Command\Command;

final class CalleesCommandTest extends CommandTestCase
{
    public function testOutputsEmptyArrayWhenNoCallees(): void
    {
        $tester = $this->runCommand(new CalleesCommand(), ['fqn' => 'App\Controllers\UserController::store']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame([], $this->decodeJson($tester));
    }

    public function testOutputsOutboundEdgesAsJson(): void
    {
        $this->seed(static function (SqliteGraph $graph): void {
            $edge = new Edge(
                'call',
                'App\Controllers\UserController::store',
                'App\Services\UserService::create',
                'app/Controllers/UserController.php',
                42,
                null,
            );

            $graph->recordFile('app/Controllers/UserController.php', 'hash123', 1000, 1234567890, [], [$edge], []);
        });

        $tester = $this->runCommand(new CalleesCommand(), ['fqn' => 'App\Controllers\UserController::store']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $data = $this->decodeJson($tester);
        self::assertSame('App\Services\UserService::create', $data[0]['callee']);
    }
}
