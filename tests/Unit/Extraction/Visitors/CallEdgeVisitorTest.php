<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Unit\Extraction\Visitors;

use Maarheeze\CodeGraph\Extraction\Visitors\CallEdgeVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

use function array_filter;
use function count;

final class CallEdgeVisitorTest extends TestCase
{
    public function testVisitorDetectsMethodCalls(): void
    {
        $code = <<<'PHP'
        <?php
        class UserService {
            public function getUser() {
                $repo = new UserRepository();
                $repo->find(1);
            }
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertNotEmpty($edges);
        self::assertTrue(count($edges) >= 2);
    }

    public function testVisitorDetectsStaticCalls(): void
    {
        $code = <<<'PHP'
        <?php
        class UserService {
            public function createUser() {
                Log::info('Creating user');
            }
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertNotEmpty($edges);
        $callEdges = array_filter($edges, static function ($edge) {
            return $edge->kind === 'calls';
        });
        self::assertNotEmpty($callEdges);
    }

    public function testVisitorDetectsInstantiations(): void
    {
        $code = <<<'PHP'
        <?php
        class UserService {
            public function createUser() {
                $user = new User();
            }
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertNotEmpty($edges);
        $instantiationEdges = array_filter($edges, static function ($edge) {
            return $edge->kind === 'instantiates';
        });
        self::assertNotEmpty($instantiationEdges);
    }

    public function testVisitorDetectsFunctionCalls(): void
    {
        $code = <<<'PHP'
        <?php
        function processData() {
            doSomething();
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertNotEmpty($edges);
        self::assertTrue(count($edges) >= 1);
    }

    public function testVisitorHandlesNamespacedCode(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Services;

        class UserService {
            public function getUser() {
                $repo = new Repository();
            }
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertNotEmpty($edges);
    }

    public function testVisitorIgnoresCallsOutsideContext(): void
    {
        $code = <<<'PHP'
        <?php
        $repo->find(1);
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertEmpty($edges);
    }

    public function testVisitorHandlesNestedCalls(): void
    {
        $code = <<<'PHP'
        <?php
        class Service {
            public function process() {
                $this->helper($this->getData());
            }
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertNotEmpty($edges);
        self::assertTrue(count($edges) >= 2);
    }

    public function testVisitorHandlesMultipleMethods(): void
    {
        $code = <<<'PHP'
        <?php
        class Service {
            public function method1() {
                $this->helper();
            }

            public function method2() {
                $this->helper();
            }
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertNotEmpty($edges);
        self::assertTrue(count($edges) >= 2);
    }

    public function testVisitorHandlesAnonymousClasses(): void
    {
        $code = <<<'PHP'
        <?php
        class Service {
            public function create() {
                return new class {
                    public function run() {
                        $this->helper();
                    }
                };
            }
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertNotEmpty($edges);
    }

    public function testVisitorHandlesInterfaceMethods(): void
    {
        $code = <<<'PHP'
        <?php
        interface Processor {
            public function process();
        }

        class Service implements Processor {
            public function process() {
                $this->helper();
            }
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertNotEmpty($edges);
    }

    public function testVisitorHandlesTraits(): void
    {
        $code = <<<'PHP'
        <?php
        trait Helper {
            public function help() {
                $this->doWork();
            }
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertNotEmpty($edges);
    }

    public function testVisitorHandlesEnums(): void
    {
        $code = <<<'PHP'
        <?php
        enum Status {
            case Active;
            case Inactive;
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertEmpty($edges);
    }

    public function testVisitorHandlesMethodCallsWithoutTarget(): void
    {
        $code = <<<'PHP'
        <?php
        class Service {
            public function process() {
                helper();
            }
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertNotEmpty($edges);
    }

    public function testVisitorDetectsCallsInGlobalFunctions(): void
    {
        $code = <<<'PHP'
        <?php
        function process() {
            doSomething();
            execute();
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertNotEmpty($edges);
        self::assertTrue(count($edges) >= 2);
    }

    public function testVisitorDetectsCallsInNamespacedFunctions(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Services;

        function process() {
            helper();
            execute();
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertNotEmpty($edges);
    }

    public function testVisitorIgnoresCallsOutsideFunctionsAndClasses(): void
    {
        $code = <<<'PHP'
        <?php
        helper();
        new User();
        execute();
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertEmpty($edges);
    }

    public function testVisitorHandlesInterfaceWithMethods(): void
    {
        $code = <<<'PHP'
        <?php
        interface Service {
            public function execute();
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertEmpty($edges);
    }

    public function testVisitorDetectsCallsInTraitMethods(): void
    {
        $code = <<<'PHP'
        <?php
        trait Logger {
            public function log() {
                write();
            }
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertNotEmpty($edges);
    }

    public function testVisitorDetectsCallsWithDynamicMethodNames(): void
    {
        $code = <<<'PHP'
        <?php
        class Service {
            public function execute() {
                $method = 'run';
                $this->$method();
            }
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertNotEmpty($edges);
    }

    public function testVisitorHandlesStaticCallsWithDynamicClass(): void
    {
        $code = <<<'PHP'
        <?php
        class Service {
            public function execute() {
                Facade::call();
            }
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertNotEmpty($edges);
    }

    public function testVisitorHandlesCallsInClosures(): void
    {
        $code = <<<'PHP'
        <?php
        $closure = function() {
            doSomething();
            Logger::info('test');
        };
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertEmpty($edges);
    }

    public function testVisitorDetectsCallsWithDefaultMethodName(): void
    {
        $code = <<<'PHP'
        <?php
        class Service {
            public function execute() {
                $method = 'test';
                $this->$method();
            }
        }
        PHP;

        $edges = $this->parseAndExtractEdges($code);

        self::assertNotEmpty($edges);
    }

    private function parseAndExtractEdges(string $sourceCode): array
    {
        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($sourceCode);
        $visitor = new CallEdgeVisitor('test.php', $sourceCode);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);

        return $visitor->edges();
    }
}
