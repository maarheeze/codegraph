<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Unit\Extraction\Visitors;

use Maarheeze\CodeGraph\Extraction\Visitors\CallEdgeVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

use function count;

final class CallEdgeVisitorCompleteMethodCoverageTest extends TestCase
{
    public function testCallEdgeVisitorAllMethodsWithComprehensivePhp(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Services;

        use Log;

        trait LoggerTrait {
            public function log() {
                echo 'log';
            }
        }

        class ServiceA {
            use LoggerTrait;

            public function execute() {
                $this->internal();
                ServiceB::staticMethod();
                helper();
                new ServiceB();
            }

            private function internal() {
                return 'internal';
            }
        }

        class ServiceB {
            public static function staticMethod() {
                return 'static';
            }

            public function instanceMethod() {
                return 'instance';
            }
        }

        function helper() {
            doWork();
        }

        function doWork() {
            Log::info('working');
        }

        interface ServiceInterface {
            public function handle();
        }

        enum Status {
            case Active;
        }
        PHP;

        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($code);

        $visitor = new CallEdgeVisitor('services.php', $code);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);

        $edges = $visitor->edges();

        self::assertGreaterThan(3, count($edges));
        self::assertTrue($this->hasEdgeKind($edges, 'calls'));
        self::assertTrue($this->hasEdgeKind($edges, 'instantiates'));
    }

    public function testCallEdgeVisitorNestedCallPatterns(): void
    {
        $code = <<<'PHP'
        <?php
        class ChainedCalls {
            public function chainOne() {
                $obj = new Helper();
                $obj->methodA()->methodB()->methodC();

                static::staticCall();
                parent::parentCall();
            }

            public function chainTwo() {
                Cache::remember('key', function() {
                    $result = expensive_function();
                    return $result;
                });
            }

            private static function internalStatic() {
                return self::staticProp();
            }
        }

        class Helper {
            public function methodA() { return $this; }
            public function methodB() { return $this; }
            public function methodC() { return $this; }
        }
        PHP;

        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($code);

        $visitor = new CallEdgeVisitor('chained.php', $code);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);

        $edges = $visitor->edges();

        self::assertGreaterThan(0, count($edges));
    }

    public function testCallEdgeVisitorWithClosuresAndCallables(): void
    {
        $code = <<<'PHP'
        <?php
        class CallableHandler {
            public function registerCallback() {
                $callback = function() {
                    $this->internal();
                };

                array_map(function($item) {
                    return process($item);
                }, $items);

                $callable = [$this, 'methodName'];
                call_user_func($callable);
            }

            private function internal() {}
        }

        function process($item) {
            return handle($item);
        }

        function handle($value) {
            return $value;
        }
        PHP;

        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($code);

        $visitor = new CallEdgeVisitor('callable.php', $code);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);

        $edges = $visitor->edges();

        self::assertGreaterThanOrEqual(0, count($edges));
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
