<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Unit\Extraction\Visitors;

use Maarheeze\CodeGraph\Extraction\Visitors\ChunkExtractor;
use Maarheeze\CodeGraph\Values\Symbol;
use PHPUnit\Framework\TestCase;

final class ChunkExtractorTest extends TestCase
{
    public function testExtractChunkFromSymbols(): void
    {
        $code = <<<'PHP'
        <?php
        class User {
            public function getName(): string {
                return $this->name;
            }
        }
        PHP;

        $symbol = new Symbol(
            'method',
            'getName',
            'App\Models\User::getName',
            'App\Models\User',
            'app/Models/User.php',
            3,
            5,
            'public',
            false,
            false,
            'public function getName(): string',
            null,
        );

        $extractor = new ChunkExtractor($code, 'app/Models/User.php');
        $chunks = $extractor->extract([$symbol]);

        self::assertCount(1, $chunks);
        self::assertSame('App\Models\User::getName', $chunks[0]->fullyQualifiedName);
        self::assertSame('method', $chunks[0]->kind);
        self::assertSame('app/Models/User.php', $chunks[0]->file);
        self::assertSame(3, $chunks[0]->startLine);
        self::assertSame(5, $chunks[0]->endLine);
        self::assertStringContainsString('getName', $chunks[0]->body);
        self::assertStringContainsString('return', $chunks[0]->body);
    }

    public function testExtractMultipleChunks(): void
    {
        $code = <<<'PHP'
        <?php
        class User {
            public function getName(): string {
                return $this->name;
            }

            public function getEmail(): string {
                return $this->email;
            }
        }
        PHP;

        $symbol1 = new Symbol(
            'method',
            'getName',
            'App\Models\User::getName',
            'App\Models\User',
            'app/Models/User.php',
            3,
            5,
            'public',
            false,
            false,
            'public function getName(): string',
            null,
        );

        $symbol2 = new Symbol(
            'method',
            'getEmail',
            'App\Models\User::getEmail',
            'App\Models\User',
            'app/Models/User.php',
            7,
            9,
            'public',
            false,
            false,
            'public function getEmail(): string',
            null,
        );

        $extractor = new ChunkExtractor($code, 'app/Models/User.php');
        $chunks = $extractor->extract([$symbol1, $symbol2]);

        self::assertCount(2, $chunks);
        self::assertSame('App\Models\User::getName', $chunks[0]->fullyQualifiedName);
        self::assertSame('App\Models\User::getEmail', $chunks[1]->fullyQualifiedName);
    }

    public function testIgnoresInvalidLineNumbers(): void
    {
        $code = <<<'PHP'
        <?php
        class User {}
        PHP;

        $symbol = new Symbol(
            'class',
            'User',
            'App\Models\User',
            null,
            'app/Models/User.php',
            999,
            1000,
            '',
            false,
            false,
            'class User',
            null,
        );

        $extractor = new ChunkExtractor($code, 'app/Models/User.php');
        $chunks = $extractor->extract([$symbol]);

        self::assertCount(0, $chunks);
    }

    public function testHandlesNegativeStartLine(): void
    {
        $code = <<<'PHP'
        <?php
        class User {}
        PHP;

        $symbol = new Symbol(
            'class',
            'User',
            'App\Models\User',
            null,
            'app/Models/User.php',
            -5,
            2,
            '',
            false,
            false,
            'class User',
            null,
        );

        $extractor = new ChunkExtractor($code, 'app/Models/User.php');
        $chunks = $extractor->extract([$symbol]);

        self::assertCount(0, $chunks);
    }

    public function testClipsEndLineToFileLength(): void
    {
        $code = <<<'PHP'
        <?php
        class User {}
        PHP;

        $symbol = new Symbol(
            'class',
            'User',
            'App\Models\User',
            null,
            'app/Models/User.php',
            1,
            999,
            '',
            false,
            false,
            'class User',
            null,
        );

        $extractor = new ChunkExtractor($code, 'app/Models/User.php');
        $chunks = $extractor->extract([$symbol]);

        self::assertCount(1, $chunks);
        self::assertStringContainsString('class User', $chunks[0]->body);
    }

    public function testExtractsEmptySymbolArray(): void
    {
        $code = <<<'PHP'
        <?php
        class User {}
        PHP;

        $extractor = new ChunkExtractor($code, 'app/Models/User.php');
        $chunks = $extractor->extract([]);

        self::assertCount(0, $chunks);
    }

    public function testPreservesLineContentAccurately(): void
    {
        $code = <<<'PHP'
        <?php
        function process(string $input): string {
            return strtoupper($input);
        }
        PHP;

        $symbol = new Symbol(
            'function',
            'process',
            'process',
            null,
            'functions.php',
            2,
            4,
            '',
            false,
            false,
            'function process(string $input): string',
            null,
        );

        $extractor = new ChunkExtractor($code, 'functions.php');
        $chunks = $extractor->extract([$symbol]);

        self::assertCount(1, $chunks);
        $body = $chunks[0]->body;
        self::assertStringContainsString('function process', $body);
        self::assertStringContainsString('strtoupper', $body);
    }
}
