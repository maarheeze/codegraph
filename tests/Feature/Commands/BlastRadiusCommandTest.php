<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Commands;

use Maarheeze\CodeGraph\Commands\BlastRadiusCommand;
use Maarheeze\CodeGraph\Storage\Sqlite\SqliteGraph;
use Maarheeze\CodeGraph\Values\Edge;
use Symfony\Component\Console\Command\Command;

final class BlastRadiusCommandTest extends CommandTestCase
{
    public function testOutputsImpactObjectAsJson(): void
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

        $tester = $this->runCommand(new BlastRadiusCommand(), ['fqn' => 'App\Services\UserService::create']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $data = $this->decodeJson($tester);
        self::assertSame('App\Services\UserService::create', $data['query']);
        self::assertSame(3, $data['depth']);
        self::assertSame(2, $data['affected_count']);
    }

    public function testRespectsDepthOption(): void
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

        $tester = $this->runCommand(new BlastRadiusCommand(), [
            'fqn' => 'App\Services\UserService::create',
            '--depth' => '1',
        ]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $data = $this->decodeJson($tester);
        self::assertSame(1, $data['depth']);
        self::assertSame(1, $data['affected_count']);
    }
}
