<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Unit\Values;

use Maarheeze\CodeGraph\Tests\TestCase;
use Maarheeze\CodeGraph\Values\Edge;

final class EdgeTest extends TestCase
{
    public function testEdgeCanBeConstructed(): void
    {
        $edge = new Edge(
            'call',
            'App\Controllers\UserController::store',
            'App\Models\User::create',
            'app/Http/Controllers/UserController.php',
            42,
            null,
        );

        self::assertSame('call', $edge->kind);
        self::assertSame('App\Controllers\UserController::store', $edge->sourceFullyQualifiedName);
        self::assertSame('App\Models\User::create', $edge->destinationFullyQualifiedName);
        self::assertSame('app/Http/Controllers/UserController.php', $edge->file);
        self::assertSame(42, $edge->line);
        self::assertNull($edge->metadata);
    }

    public function testEdgeWithMetadata(): void
    {
        $edge = new Edge(
            'inheritance',
            'App\Models\BaseModel',
            'Illuminate\Database\Eloquent\Model',
            'app/Models/BaseModel.php',
            5,
            '{"visibility":"public"}',
        );

        self::assertSame('inheritance', $edge->kind);
        self::assertSame('{"visibility":"public"}', $edge->metadata);
    }
}
