<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Unit\Extraction\Visitors;

use Maarheeze\CodeGraph\Extraction\Visitors\CallEdgeVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

use function count;

final class CallVisitorMethodCoverageTest extends TestCase
{
    public function testAllCallVisitorMethodsExecuted(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Services;

        use Helpers\Log;

        interface Handler {
            public function execute();
        }

        trait LoggerTrait {
            public function log() {
                echo('logging');
            }
        }

        enum Status {
            case Active;
        }

        class UserService implements Handler {
            use LoggerTrait;

            public function execute() {
                $user = new User();
                $this->process($user);
                Log::info('done');
                helper();
            }

            private function process($user) {
                return $user;
            }
        }

        function globalHelper() {
            doWork();
        }
        PHP;

        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($code);

        $visitor = new CallEdgeVisitor('test.php', $code);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);

        $edges = $visitor->edges();

        self::assertGreaterThan(0, count($edges));
        self::assertTrue($this->hasEdgeKind($edges, 'calls'));
        self::assertTrue($this->hasEdgeKind($edges, 'instantiates'));
    }

    public function testCallVisitorWithComplexCallPatterns(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App;

        class Service {
            public function execute() {
                // Instance method call
                $this->helper();

                // Static call
                Cache::get('key');

                // Function call
                strlen('test');

                // New instance
                $obj = new stdClass();
            }

            private function helper() {
            }
        }

        trait Helper {
            public function log() {
                write();
            }
        }
        PHP;

        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($code);

        $visitor = new CallEdgeVisitor('test.php', $code);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);

        $edges = $visitor->edges();

        self::assertGreaterThanOrEqual(4, count($edges));
    }

    /**
     * @param array<int, mixed> $edges
     */
    private function hasEdgeKind(array $edges, string $kind): bool
    {
        foreach ($edges as $edge) {
            if ($edge->kind === $kind) {
                return true;
            }
        }

        return false;
    }
}
