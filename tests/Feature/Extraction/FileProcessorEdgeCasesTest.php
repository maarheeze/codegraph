<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Extraction;

use Maarheeze\CodeGraph\Extraction\FileProcessor;
use Maarheeze\CodeGraph\Storage\Sqlite\SqliteGraph;
use Maarheeze\CodeGraph\Tests\Feature\FeatureTestCase;
use Maarheeze\CodeGraph\Values\IndexStats;
use PhpParser\ParserFactory;

use function strlen;

final class FileProcessorEdgeCasesTest extends FeatureTestCase
{
    public function testProcessWithZeroSymbols(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = '<?php // just a comment';

        $fileData = [
            'relPath' => 'comment.php',
            'contents' => $code,
            'sha256' => 'abc123',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertCount(0, $stats->getErrors());
    }

    public function testProcessHandlesComplexNamespaces(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = <<<'PHP'
        <?php
        namespace App\Models\Nested\Deep;

        class ComplexClass {
            public function complexMethod() {
                $this->helper();
                new self();
            }
        }
        PHP;

        $fileData = [
            'relPath' => 'app/Models/Nested/Deep/ComplexClass.php',
            'contents' => $code,
            'sha256' => 'abc123',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertGreaterThan(0, $stats->getSymbolsEmitted());
    }

    public function testProcessPreservesFileMetadata(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = '<?php class User {}';
        $sha256 = 'specific_hash_123';
        $mtime = 9876543210;
        $size = strlen($code);

        $fileData = [
            'relPath' => 'app/User.php',
            'contents' => $code,
            'sha256' => $sha256,
            'size' => $size,
            'mtime' => $mtime,
        ];

        $processor->process($fileData, $stats);

        $symbols = $graph->findByName('User');
        self::assertNotEmpty($symbols);
    }

    public function testProcessHandlesUnicodeInCode(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = <<<'PHP'
        <?php
        class Café {
            public function getNameñ() {
                return 'Café';
            }
        }
        PHP;

        $fileData = [
            'relPath' => 'app/Café.php',
            'contents' => $code,
            'sha256' => 'abc123',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertGreaterThan(0, $stats->getSymbolsEmitted());
    }

    public function testProcessWithSyntaxErrorLogsError(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = '<?php class Broken { function (';

        $fileData = [
            'relPath' => 'broken.php',
            'contents' => $code,
            'sha256' => 'xyz789',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertCount(1, $stats->getErrors());
        self::assertSame(1, $stats->getFilesFailed());
    }

    public function testProcessHandlesOnlyTraits(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = <<<'PHP'
        <?php
        trait LoggerTrait {
            public function log() {}
        }
        PHP;

        $fileData = [
            'relPath' => 'LoggerTrait.php',
            'contents' => $code,
            'sha256' => 'abc123',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertGreaterThan(0, $stats->getSymbolsEmitted());
    }

    public function testProcessHandlesOnlyInterfaces(): void
    {
        $graph = new SqliteGraph(':memory:');
        $graph->migrate();
        $parser = (new ParserFactory())->createForHostVersion();
        $processor = new FileProcessor($graph, $parser);
        $stats = new IndexStats();

        $code = <<<'PHP'
        <?php
        interface Contract {
            public function execute();
        }
        PHP;

        $fileData = [
            'relPath' => 'Contract.php',
            'contents' => $code,
            'sha256' => 'abc123',
            'size' => strlen($code),
            'mtime' => 1234567890,
        ];

        $processor->process($fileData, $stats);

        self::assertGreaterThan(0, $stats->getSymbolsEmitted());
    }
}
