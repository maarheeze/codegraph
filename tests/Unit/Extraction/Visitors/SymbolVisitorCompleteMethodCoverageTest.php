<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Unit\Extraction\Visitors;

use Maarheeze\CodeGraph\Extraction\Visitors\SignatureBuilder;
use Maarheeze\CodeGraph\Extraction\Visitors\SymbolFactory;
use Maarheeze\CodeGraph\Extraction\Visitors\SymbolVisitor;
use Maarheeze\CodeGraph\Extraction\Visitors\TypeFormatter;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

use function array_filter;
use function count;
use function current;

final class SymbolVisitorCompleteMethodCoverageTest extends TestCase
{
    public function testSymbolVisitorCoverageWithCompletePhpStructure(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Services;

        use DateTime;
        use App\Models\User;

        abstract class AbstractService {
            public abstract function execute();
            protected static function helper() {}
            private function internal() {}
        }

        final class UserService extends AbstractService {
            public function execute() {
                return 'executed';
            }

            private static function staticHelper($param): string {
                return $param;
            }
        }

        interface ServiceContract {
            public function handle();
        }

        trait LoggableTrait {
            public function log() {}
        }

        enum Status {
            case Active;
            case Inactive;
        }

        function globalFunction() {}

        namespace App\Helpers;
        function helperFunction() {}
        PHP;

        $symbols = $this->extractSymbols('Services.php', $code);

        self::assertGreaterThan(10, count($symbols));
        self::assertGreaterThan(0, count(array_filter($symbols, static function ($s) {
            return $s->kind === 'class';
        })));
        self::assertGreaterThan(0, count(array_filter($symbols, static function ($s) {
            return $s->kind === 'function';
        })));
        self::assertGreaterThan(0, count(array_filter($symbols, static function ($s) {
            return $s->kind === 'interface';
        })));
        self::assertGreaterThan(0, count(array_filter($symbols, static function ($s) {
            return $s->kind === 'trait';
        })));
        self::assertGreaterThan(0, count(array_filter($symbols, static function ($s) {
            return $s->kind === 'enum';
        })));
    }

    public function testSymbolVisitorWithInheritanceHierarchy(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Models;

        class BaseModel {
            public function save() {}
        }

        class User extends BaseModel {
            public function getName(): string {}
            protected function validate(): void {}
            private function hash(): string {}
        }

        class Admin extends User {
            public function approveUser() {}
        }

        interface Auditable {
            public function audit();
        }

        class AuditableUser extends User implements Auditable {
            public function audit() {}
        }
        PHP;

        $symbols = $this->extractSymbols('models.php', $code);

        $classes = array_filter($symbols, static function ($s) {
            return $s->kind === 'class';
        });
        self::assertGreaterThanOrEqual(4, count($classes));

        $methods = array_filter($symbols, static function ($s) {
            return $s->kind === 'method';
        });
        self::assertGreaterThan(5, count($methods));
    }

    public function testSymbolVisitorWithVisibilityModifiers(): void
    {
        $code = <<<'PHP'
        <?php
        class AccessControl {
            public $publicProperty;
            protected $protectedProperty;
            private $privateProperty;

            public function publicMethod() {}
            protected function protectedMethod() {}
            private function privateMethod() {}

            public static function staticPublic() {}
            private static function staticPrivate() {}
        }
        PHP;

        $symbols = $this->extractSymbols('access.php', $code);

        $methods = array_filter($symbols, static function ($s) {
            return $s->kind === 'method';
        });
        self::assertGreaterThanOrEqual(5, count($methods));

        $publicMethods = array_filter($methods, static function ($s) {
            return $s->visibility === 'public';
        });
        self::assertGreaterThan(0, count($publicMethods));
    }

    public function testSymbolVisitorWithAnonymousClassesIgnored(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App;

        class Factory {
            public function create() {
                return new class {
                    public function method() {}
                };
            }

            public function createNested() {
                return new class extends BaseClass {
                    public function nested() {
                        return new class implements Interface1 {
                            public function implement() {}
                        };
                    }
                };
            }
        }
        PHP;

        $symbols = $this->extractSymbols('factory.php', $code);

        $classes = array_filter($symbols, static function ($s) {
            return $s->kind === 'class';
        });
        self::assertSame(1, count($classes));
        self::assertSame('Factory', $classes[0]->name);
    }

    public function testSymbolVisitorWithDeeplyNestedStructures(): void
    {
        $code = <<<'PHP'
        <?php
        namespace Level1\Level2\Level3;

        class DeeplyNested {
            public function levelOne() {
                function nestedFunction() {}
            }

            public function levelTwo() {
                class NestedClass {
                    public function nestedMethod() {}
                }
            }
        }
        PHP;

        $symbols = $this->extractSymbols('nested.php', $code);

        self::assertGreaterThan(0, count($symbols));
        $mainClass = current(array_filter($symbols, static function ($s) {
            return $s->kind === 'class' && $s->name === 'DeeplyNested';
        }));
        self::assertNotFalse($mainClass);
    }

    /**
     * @return array<int, mixed>
     */
    private function extractSymbols(string $filePath, string $code): array
    {
        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($code);

        $typeFormatter = new TypeFormatter();
        $signatureBuilder = new SignatureBuilder($typeFormatter);
        $symbolFactory = new SymbolFactory($signatureBuilder);

        $visitor = new SymbolVisitor($filePath, $code, $symbolFactory);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);

        return $visitor->symbols();
    }
}
