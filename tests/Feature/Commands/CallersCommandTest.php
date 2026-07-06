<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Commands;

use Maarheeze\CodeGraph\Commands\CallersCommand;
use Maarheeze\CodeGraph\Storage\Sqlite\SqliteGraph;
use Maarheeze\CodeGraph\Values\Edge;
use Symfony\Component\Console\Command\Command;

final class CallersCommandTest extends CommandTestCase
{
    public function testOutputsEmptyArrayWhenNoCallers(): void
    {
        $tester = $this->runCommand(new CallersCommand(), ['fqn' => 'App\Services\UserService::create']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame([], $this->decodeJson($tester));
    }

    public function testOutputsInboundEdgesAsJson(): void
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

        $tester = $this->runCommand(new CallersCommand(), ['fqn' => 'App\Services\UserService::create']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $data = $this->decodeJson($tester);
        self::assertSame('App\Controllers\UserController::store', $data[0]['caller']);
        self::assertSame('App\Services\UserService::create', $data[0]['callee']);
    }
}
