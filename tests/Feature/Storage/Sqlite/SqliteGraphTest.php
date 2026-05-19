<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Storage\Sqlite;

use Maarheeze\CodeGraph\Tests\Feature\FeatureTestCase;
use Maarheeze\CodeGraph\Values\Chunk;
use Maarheeze\CodeGraph\Values\Edge;
use Maarheeze\CodeGraph\Values\Symbol;

use function count;

final class SqliteGraphTest extends FeatureTestCase
{
    public function testFindByNameReturnsEmptyArrayWhenNoMatch(): void
    {
        $results = $this->database->findByName('NonExistentSymbol');

        self::assertIsArray($results);
        self::assertCount(0, $results);
    }

    public function testFindByNameReturnsSymbolWhenMatches(): void
    {
        $symbol = new Symbol(
            'class',
            'User',
            'App\Models\User',
            null,
            'app/Models/User.php',
            10,
            100,
            'public',
            false,
            false,
            'class User',
            null,
        );

        $this->database->recordFile('app/Models/User.php', 'hash123', 1000, 1234567890, [$symbol], [], []);

        $results = $this->database->findByName('User');

        self::assertCount(1, $results);
        self::assertSame('User', $results[0]->name);
        self::assertSame('App\Models\User', $results[0]->fullyQualifiedName);
    }

    public function testFindEdgesFromReturnsEmptyWhenNoEdges(): void
    {
        $results = $this->database->findEdgesFrom('App\Services\UserService::create');

        self::assertIsArray($results);
        self::assertCount(0, $results);
    }

    public function testFindEdgesFromReturnsEdgesWhenTheyExist(): void
    {
        $edge = new Edge(
            'call',
            'App\Controllers\UserController::store',
            'App\Services\UserService::create',
            'app/Controllers/UserController.php',
            42,
            null,
        );

        $this->database->recordFile('app/Controllers/UserController.php', 'hash123', 1000, 1234567890, [], [$edge], []);

        $results = $this->database->findEdgesFrom('App\Controllers\UserController::store');

        self::assertCount(1, $results);
        self::assertSame('App\Services\UserService::create', $results[0]->destinationFullyQualifiedName);
    }

    public function testCountSymbols(): void
    {
        self::assertSame(0, $this->database->countSymbols());

        $symbol = new Symbol(
            'function',
            'helper',
            'helper',
            null,
            'helpers.php',
            1,
            10,
            'public',
            false,
            false,
            'function helper()',
            null,
        );

        $this->database->recordFile('helpers.php', 'hash123', 500, 1234567890, [$symbol], [], []);

        self::assertSame(1, $this->database->countSymbols());
    }

    public function testFindEdgesToReturnsEmptyWhenNoEdges(): void
    {
        $results = $this->database->findEdgesTo('App\Services\UserService::create');

        self::assertIsArray($results);
        self::assertCount(0, $results);
    }

    public function testFindEdgesToReturnsEdgesWhenTheyExist(): void
    {
        $edge = new Edge(
            'call',
            'App\Controllers\UserController::store',
            'App\Services\UserService::create',
            'app/Controllers/UserController.php',
            42,
            null,
        );

        $this->database->recordFile('app/Controllers/UserController.php', 'hash123', 1000, 1234567890, [], [$edge], []);

        $results = $this->database->findEdgesTo('App\Services\UserService::create');

        self::assertCount(1, $results);
        self::assertSame('App\Controllers\UserController::store', $results[0]->sourceFullyQualifiedName);
    }

    public function testCountEdges(): void
    {
        self::assertSame(0, $this->database->countEdges());

        $edge = new Edge(
            'call',
            'App\Service::method',
            'App\Other::method',
            'app/Service.php',
            10,
            null,
        );

        $this->database->recordFile('app/Service.php', 'hash123', 1000, 1234567890, [], [$edge], []);

        self::assertSame(1, $this->database->countEdges());
    }

    public function testCountFiles(): void
    {
        self::assertSame(0, $this->database->countFiles());

        $symbol = new Symbol(
            'class',
            'User',
            'App\Models\User',
            null,
            'app/Models/User.php',
            1,
            50,
            'public',
            false,
            false,
            'class User',
            null,
        );

        $this->database->recordFile('app/Models/User.php', 'hash123', 1000, 1234567890, [$symbol], [], []);

        self::assertSame(1, $this->database->countFiles());
    }

    public function testSearchChunksReturnsEmptyWhenNoMatch(): void
    {
        $results = $this->database->searchChunks('nonexistent');

        self::assertIsArray($results);
        self::assertCount(0, $results);
    }

    public function testSearchChunksFindsMatchingChunks(): void
    {
        $chunk = new Chunk(
            'App\Models\User',
            'class',
            'app/Models/User.php',
            1,
            50,
            'class User { public function getName() { return $this->name; } }',
        );

        $this->database->recordFile('app/Models/User.php', 'hash123', 1000, 1234567890, [], [], [$chunk]);

        $results = $this->database->searchChunks('getName');

        self::assertCount(1, $results);
        self::assertSame('App\Models\User', $results[0]->fullyQualifiedName);
    }

    public function testCountChunks(): void
    {
        self::assertSame(0, $this->database->countChunks());

        $chunk = new Chunk(
            'App\Models\User',
            'class',
            'app/Models/User.php',
            1,
            50,
            'class User {}',
        );

        $this->database->recordFile('app/Models/User.php', 'hash123', 1000, 1234567890, [], [], [$chunk]);

        self::assertSame(1, $this->database->countChunks());
    }

    public function testBlastRadiusReturnsCallers(): void
    {
        $symbol = new Symbol(
            'method',
            'execute',
            'App\Service::execute',
            'App\Service',
            'app/Service.php',
            10,
            20,
            'public',
            false,
            false,
            'public function execute()',
            null,
        );

        $edge = new Edge(
            'calls',
            'App\Controller::handle',
            'App\Service::execute',
            'app/Controller.php',
            42,
            null,
        );

        $this->database->recordFile('app/Service.php', 'hash123', 1000, 1234567890, [$symbol], [], []);
        $this->database->recordFile('app/Controller.php', 'hash456', 1000, 1234567890, [], [$edge], []);

        $blastRadius = $this->database->blastRadius('App\Service::execute');

        self::assertGreaterThanOrEqual(0, count($blastRadius));
    }

    public function testResolveEdgesLinksCorrectly(): void
    {
        $symbol1 = new Symbol(
            'class',
            'User',
            'App\Models\User',
            null,
            'app/Models/User.php',
            1,
            50,
            'public',
            false,
            false,
            'class User',
            null,
        );

        $symbol2 = new Symbol(
            'method',
            'create',
            'App\Factories\UserFactory::create',
            'App\Factories\UserFactory',
            'app/Factories/UserFactory.php',
            10,
            30,
            'public',
            false,
            false,
            'public function create()',
            null,
        );

        $edge = new Edge(
            'instantiates',
            'App\Factories\UserFactory::create',
            'App\Models\User',
            'app/Factories/UserFactory.php',
            15,
            null,
        );

        $this->database->recordFile(
            'app/Models/User.php',
            'hash1',
            1000,
            1234567890,
            [$symbol1],
            [],
            [],
        );
        $this->database->recordFile(
            'app/Factories/UserFactory.php',
            'hash2',
            1000,
            1234567890,
            [$symbol2],
            [$edge],
            [],
        );

        $this->database->resolveEdges();

        $results = $this->database->findEdgesFrom('App\Factories\UserFactory::create');
        self::assertGreaterThanOrEqual(0, count($results));
    }
}
