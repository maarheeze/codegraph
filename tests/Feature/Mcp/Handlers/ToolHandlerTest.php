<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Mcp\Handlers;

use Maarheeze\CodeGraph\Mcp\Handlers\ToolHandler;
use Maarheeze\CodeGraph\Tests\Feature\FeatureTestCase;
use Maarheeze\CodeGraph\Values\Chunk;
use Maarheeze\CodeGraph\Values\Edge;
use Maarheeze\CodeGraph\Values\Symbol;

final class ToolHandlerTest extends FeatureTestCase
{
    public function testSearchReturnsEmptyArrayWhenNoSymbols(): void
    {
        $handler = new ToolHandler($this->database);
        $result = $handler->handle('codegraph_search', ['name' => 'NonExistent']);

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testSearchReturnsSymbols(): void
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

        $handler = new ToolHandler($this->database);
        $result = $handler->handle('codegraph_search', ['name' => 'UserService']);

        self::assertCount(1, $result);
        self::assertSame('UserService', $result[0]['name']);
        self::assertSame('App\Services\UserService', $result[0]['fqn']);
    }

    public function testCallersReturnsEmptyWhenNoCallers(): void
    {
        $handler = new ToolHandler($this->database);
        $result = $handler->handle('codegraph_callers', ['fqn' => 'App\Services\UserService::create']);

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testCallersReturnsEdges(): void
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

        $handler = new ToolHandler($this->database);
        $result = $handler->handle('codegraph_callers', ['fqn' => 'App\Services\UserService::create']);

        self::assertCount(1, $result);
        self::assertSame('App\Controllers\UserController::store', $result[0]['caller']);
        self::assertSame('App\Services\UserService::create', $result[0]['callee']);
    }

    public function testCalleesReturnsEmptyWhenNoCallees(): void
    {
        $handler = new ToolHandler($this->database);
        $result = $handler->handle('codegraph_callees', ['fqn' => 'App\Controllers\UserController::store']);

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testCalleesReturnsEdges(): void
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

        $handler = new ToolHandler($this->database);
        $result = $handler->handle('codegraph_callees', ['fqn' => 'App\Controllers\UserController::store']);

        self::assertCount(1, $result);
        self::assertSame('App\Services\UserService::create', $result[0]['callee']);
    }

    public function testBlastRadiusReturnsSymbolWhenNoCallers(): void
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

        $handler = new ToolHandler($this->database);
        $result = $handler->handle('codegraph_blast_radius', ['fqn' => 'App\Services\UserService']);

        self::assertIsArray($result);
        self::assertArrayHasKey('query', $result);
        self::assertArrayHasKey('affected_count', $result);
        self::assertSame('App\Services\UserService', $result['query']);
        self::assertSame(1, $result['affected_count']);
        self::assertCount(1, $result['affected_symbols']);
    }

    public function testBlastRadiusCalculatesImpact(): void
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

        $handler = new ToolHandler($this->database);
        $result = $handler->handle('codegraph_blast_radius', [
            'fqn' => 'App\Services\UserService::create',
            'depth' => 1,
        ]);

        self::assertSame(1, $result['affected_count']);
        self::assertCount(1, $result['affected_symbols']);
    }

    public function testSearchChunksReturnsEmptyWhenNoMatch(): void
    {
        $handler = new ToolHandler($this->database);
        $result = $handler->handle('codegraph_search_chunks', ['query' => 'nonexistent']);

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testSearchChunksFindsMatches(): void
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

        $handler = new ToolHandler($this->database);
        $result = $handler->handle('codegraph_search_chunks', ['query' => 'getName']);

        self::assertCount(1, $result);
        self::assertSame('App\Models\User', $result[0]['fqn']);
        self::assertStringContainsString('getName', $result[0]['body']);
    }
}
