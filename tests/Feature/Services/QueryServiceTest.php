<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Services;

use Maarheeze\CodeGraph\Services\QueryService;
use Maarheeze\CodeGraph\Tests\Feature\FeatureTestCase;
use Maarheeze\CodeGraph\Values\Chunk;
use Maarheeze\CodeGraph\Values\Edge;
use Maarheeze\CodeGraph\Values\Symbol;

use function str_repeat;
use function strlen;

final class QueryServiceTest extends FeatureTestCase
{
    public function testSearchReturnsEmptyArrayWhenNoSymbols(): void
    {
        $service = new QueryService($this->database);
        $result = $service->search('NonExistent');

        self::assertSame([], $result);
    }

    public function testSearchMapsSymbolFields(): void
    {
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

        $this->database->recordFile('app/Services/UserService.php', 'hash123', 1000, 1234567890, [$symbol], [], []);

        $service = new QueryService($this->database);
        $result = $service->search('UserService');

        self::assertCount(1, $result);
        self::assertSame([
            'kind' => 'class',
            'name' => 'UserService',
            'fqn' => 'App\Services\UserService',
            'file' => 'app/Services/UserService.php',
            'line' => 10,
            'signature' => 'class UserService',
        ], $result[0]);
    }

    public function testCallersReturnsEmptyWhenNoCallers(): void
    {
        $service = new QueryService($this->database);
        $result = $service->callers('App\Services\UserService::create');

        self::assertSame([], $result);
    }

    public function testCallersMapsInboundEdgeFields(): void
    {
        $edge = new Edge(
            'call',
            'App\Controllers\UserController::store',
            'App\Services\UserService::create',
            'app/Controllers/UserController.php',
            42,
            null,
        );

        $this->database->recordFile(
            'app/Controllers/UserController.php',
            'hash123',
            1000,
            1234567890,
            [],
            [$edge],
            [],
        );

        $service = new QueryService($this->database);
        $result = $service->callers('App\Services\UserService::create');

        self::assertCount(1, $result);
        self::assertSame([
            'kind' => 'call',
            'caller' => 'App\Controllers\UserController::store',
            'callee' => 'App\Services\UserService::create',
            'file' => 'app/Controllers/UserController.php',
            'line' => 42,
        ], $result[0]);
    }

    public function testCalleesReturnsEmptyWhenNoCallees(): void
    {
        $service = new QueryService($this->database);
        $result = $service->callees('App\Controllers\UserController::store');

        self::assertSame([], $result);
    }

    public function testCalleesMapsOutboundEdgeFields(): void
    {
        $edge = new Edge(
            'call',
            'App\Controllers\UserController::store',
            'App\Services\UserService::create',
            'app/Controllers/UserController.php',
            42,
            null,
        );

        $this->database->recordFile(
            'app/Controllers/UserController.php',
            'hash123',
            1000,
            1234567890,
            [],
            [$edge],
            [],
        );

        $service = new QueryService($this->database);
        $result = $service->callees('App\Controllers\UserController::store');

        self::assertCount(1, $result);
        self::assertSame('App\Controllers\UserController::store', $result[0]['caller']);
        self::assertSame('App\Services\UserService::create', $result[0]['callee']);
    }

    public function testBlastRadiusUsesDefaultDepth(): void
    {
        $symbol = new Symbol(
            'class',
            'UserService',
            'App\Services\UserService',
            null,
            'app/Services/UserService.php',
            1,
            100,
            'public',
            false,
            false,
            'class UserService',
            null,
        );

        $this->database->recordFile('app/Services/UserService.php', 'hash123', 1000, 1234567890, [$symbol], [], []);

        $service = new QueryService($this->database);
        $result = $service->blastRadius('App\Services\UserService');

        self::assertSame('App\Services\UserService', $result['query']);
        self::assertSame(3, $result['depth']);
        self::assertSame(1, $result['affected_count']);
        self::assertCount(1, $result['affected_symbols']);
    }

    public function testBlastRadiusRespectsCustomDepth(): void
    {
        $edge = new Edge(
            'call',
            'App\Controllers\UserController::store',
            'App\Services\UserService::create',
            'app/Controllers/UserController.php',
            42,
            null,
        );

        $this->database->recordFile(
            'app/Controllers/UserController.php',
            'hash123',
            1000,
            1234567890,
            [],
            [$edge],
            [],
        );

        $service = new QueryService($this->database);
        $result = $service->blastRadius('App\Services\UserService::create', 1);

        self::assertSame(1, $result['depth']);
        self::assertSame(1, $result['affected_count']);
        self::assertCount(1, $result['affected_symbols']);
    }

    public function testSearchChunksReturnsEmptyWhenNoMatch(): void
    {
        $service = new QueryService($this->database);
        $result = $service->searchChunks('nonexistent');

        self::assertSame([], $result);
    }

    public function testSearchChunksMapsChunkFields(): void
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

        $service = new QueryService($this->database);
        $result = $service->searchChunks('getName');

        self::assertCount(1, $result);
        self::assertSame('App\Models\User', $result[0]['fqn']);
        self::assertSame('class', $result[0]['kind']);
        self::assertSame('app/Models/User.php', $result[0]['file']);
        self::assertSame('1-50', $result[0]['lines']);
        self::assertStringContainsString('getName', $result[0]['body']);
    }

    public function testSearchChunksTruncatesBodyToFiveHundredCharacters(): void
    {
        $body = str_repeat('alpha ', 100);
        $chunk = new Chunk(
            'App\Models\LargeClass',
            'class',
            'app/Models/LargeClass.php',
            1,
            80,
            $body,
        );

        $this->database->recordFile('app/Models/LargeClass.php', 'hash123', 1000, 1234567890, [], [], [$chunk]);

        $service = new QueryService($this->database);
        $result = $service->searchChunks('alpha');

        self::assertCount(1, $result);
        self::assertSame(500, strlen($result[0]['body']));
    }
}
