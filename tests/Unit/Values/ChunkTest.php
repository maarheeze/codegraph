<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Unit\Values;

use Maarheeze\CodeGraph\Tests\TestCase;
use Maarheeze\CodeGraph\Values\Chunk;

final class ChunkTest extends TestCase
{
    public function testChunkCanBeConstructed(): void
    {
        $chunk = new Chunk(
            'App\Models\User::create',
            'method',
            'app/Models/User.php',
            42,
            50,
            'public static function create(array $attrs = []): static { return static::query()->create($attrs); }',
        );

        self::assertSame('App\Models\User::create', $chunk->fullyQualifiedName);
        self::assertSame('method', $chunk->kind);
        self::assertSame('app/Models/User.php', $chunk->file);
        self::assertSame(42, $chunk->startLine);
        self::assertSame(50, $chunk->endLine);
        self::assertStringContainsString('create', $chunk->body);
    }

    public function testChunkStoresCompleteBody(): void
    {
        $body = <<<'PHP'
public function handle(): void
{
    $this->info('Processing...');
}
PHP;

        $chunk = new Chunk(
            'App\Console\Commands\ProcessCommand::handle',
            'method',
            'app/Console/Commands/ProcessCommand.php',
            10,
            14,
            $body,
        );

        self::assertSame($body, $chunk->body);
        self::assertSame(10, $chunk->startLine);
        self::assertSame(14, $chunk->endLine);
    }
}
