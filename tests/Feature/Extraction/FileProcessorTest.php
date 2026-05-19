<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Extraction;

use Maarheeze\CodeGraph\Extraction\FileProcessor;
use Maarheeze\CodeGraph\Storage\Sqlite\SqliteGraph;
use Maarheeze\CodeGraph\Tests\Feature\FeatureTestCase;
use Maarheeze\CodeGraph\Values\IndexStats;
use PhpParser\ParserFactory;

use function strlen;

final class FileProcessorTest extends FeatureTestCase
{
    public function testProcessValidPhpFile(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = <<<'PHP'
        <?php
        class User {
            public function getName(): string {
                return 'John';
            }
        }
        PHP;

        $fileData = [
            'relPath' => 'app/Models/User.php',
            'contents' => $code,
            'sha256' => 'abc123',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertGreaterThan(0, $stats->getSymbolsEmitted());
        self::assertCount(0, $stats->getErrors());
    }

    public function testProcessHandlesParseErrors(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $invalidCode = <<<'PHP'
        <?php
        class User {
            public function getName(
        PHP;

        $fileData = [
            'relPath' => 'app/Models/User.php',
            'contents' => $invalidCode,
            'sha256' => 'xyz789',
            'size' => strlen($invalidCode),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertCount(1, $stats->getErrors());
        self::assertSame(1, $stats->getFilesFailed());
    }

    public function testProcessExtractsSymbols(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = <<<'PHP'
        <?php
        namespace App\Models;

        class User {
            public function getName(): string {
                return 'John';
            }

            public function getEmail(): string {
                return 'john@example.com';
            }
        }
        PHP;

        $fileData = [
            'relPath' => 'app/Models/User.php',
            'contents' => $code,
            'sha256' => 'abc123',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertGreaterThanOrEqual(2, $stats->getSymbolsEmitted());
    }

    public function testProcessExtractsEdges(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = <<<'PHP'
        <?php
        class User extends Model implements Auditable {
            public function getName() {
                $this->helper();
            }
        }
        PHP;

        $fileData = [
            'relPath' => 'app/Models/User.php',
            'contents' => $code,
            'sha256' => 'abc123',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertGreaterThan(0, $stats->getEdgesEmitted());
    }

    public function testProcessRecordsFileInDatabase(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = <<<'PHP'
        <?php
        class User {}
        PHP;

        $fileData = [
            'relPath' => 'app/Models/User.php',
            'contents' => $code,
            'sha256' => 'abc123def456',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        $symbols = $graph->findByName('User');
        self::assertNotEmpty($symbols);
    }

    public function testProcessHandlesEmptyFile(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = '<?php';

        $fileData = [
            'relPath' => 'config.php',
            'contents' => $code,
            'sha256' => 'xyz789',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertCount(0, $stats->getErrors());
    }

    public function testProcessHandlesFileWithOnlyComments(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = <<<'PHP'
        <?php
        // This is a comment file
        // It has no classes or functions
        PHP;

        $fileData = [
            'relPath' => 'comments.php',
            'contents' => $code,
            'sha256' => 'xyz789',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertCount(0, $stats->getErrors());
    }

    public function testProcessExtractsChunks(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = <<<'PHP'
        <?php
        class User {
            public function getName(): string {
                return 'John Doe';
            }
        }
        PHP;

        $fileData = [
            'relPath' => 'app/Models/User.php',
            'contents' => $code,
            'sha256' => 'abc123',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertGreaterThan(0, $stats->getChunksEmitted());
    }

    public function testProcessHandlesMultipleClasses(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = <<<'PHP'
        <?php
        class User {}
        class Post {}
        class Comment {}
        PHP;

        $fileData = [
            'relPath' => 'app/Models/Entities.php',
            'contents' => $code,
            'sha256' => 'abc123',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertGreaterThanOrEqual(3, $stats->getSymbolsEmitted());
    }

    public function testProcessHandlesInterfacesAndTraits(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = <<<'PHP'
        <?php
        interface Repository {}
        trait Timestamps {}
        class User implements Repository {
            use Timestamps;
        }
        PHP;

        $fileData = [
            'relPath' => 'app/Models/User.php',
            'contents' => $code,
            'sha256' => 'abc123',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertGreaterThanOrEqual(3, $stats->getSymbolsEmitted());
    }

    public function testProcessHandlesNamespacedCode(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = <<<'PHP'
        <?php
        namespace App\Models\Scopes;

        class UserScope {
            public function active() {}
        }
        PHP;

        $fileData = [
            'relPath' => 'app/Models/Scopes/UserScope.php',
            'contents' => $code,
            'sha256' => 'abc123',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertGreaterThan(0, $stats->getSymbolsEmitted());
    }

    public function testProcessWithPseudoFileWhenNoSymbolsExtracted(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = <<<'PHP'
        <?php
        // Just a comment file, no actual code
        PHP;

        $fileData = [
            'relPath' => 'config/comments.php',
            'contents' => $code,
            'sha256' => 'abc123',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertGreaterThanOrEqual(1, $stats->getSymbolsEmitted());
    }

    public function testProcessRecordsAllEdgeTypes(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = <<<'PHP'
        <?php
        class User extends BaseModel implements Auditable {
            use Timestamps;
            public function __construct() {
                $x = new Helper();
                Helper::staticMethod();
                $this->helper();
            }
        }
        PHP;

        $fileData = [
            'relPath' => 'app/Models/User.php',
            'contents' => $code,
            'sha256' => 'abc123',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertGreaterThan(0, $stats->getSymbolsEmitted());
        self::assertGreaterThan(0, $stats->getEdgesEmitted());
        self::assertGreaterThan(0, $stats->getChunksEmitted());
    }
}
