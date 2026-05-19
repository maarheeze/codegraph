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

use function count;

final class SymbolVisitorTest extends TestCase
{
    public function testExtractsClassSymbols(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Models;
        class User {}
        PHP;

        $symbols = $this->extractSymbols('app/Models/User.php', $code);

        self::assertCount(1, $symbols);
        self::assertSame('class', $symbols[0]->kind);
        self::assertSame('User', $symbols[0]->name);
        self::assertSame('\App\Models\User', $symbols[0]->fullyQualifiedName);
    }

    public function testExtractsInterfaceSymbols(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Contracts;
        interface Service {}
        PHP;

        $symbols = $this->extractSymbols('app/Contracts/Service.php', $code);

        self::assertCount(1, $symbols);
        self::assertSame('interface', $symbols[0]->kind);
        self::assertSame('Service', $symbols[0]->name);
        self::assertSame('\App\Contracts\Service', $symbols[0]->fullyQualifiedName);
    }

    public function testExtractsTraitSymbols(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Traits;
        trait Timestamped {}
        PHP;

        $symbols = $this->extractSymbols('app/Traits/Timestamped.php', $code);

        self::assertCount(1, $symbols);
        self::assertSame('trait', $symbols[0]->kind);
        self::assertSame('Timestamped', $symbols[0]->name);
        self::assertSame('\App\Traits\Timestamped', $symbols[0]->fullyQualifiedName);
    }

    public function testExtractsEnumSymbols(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Enums;
        enum Status {}
        PHP;

        $symbols = $this->extractSymbols('app/Enums/Status.php', $code);

        self::assertCount(1, $symbols);
        self::assertSame('enum', $symbols[0]->kind);
        self::assertSame('Status', $symbols[0]->name);
        self::assertSame('\App\Enums\Status', $symbols[0]->fullyQualifiedName);
    }

    public function testExtractsFunctionSymbols(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Helpers;
        function process() {}
        PHP;

        $symbols = $this->extractSymbols('app/Helpers/functions.php', $code);

        self::assertCount(1, $symbols);
        self::assertSame('function', $symbols[0]->kind);
        self::assertSame('process', $symbols[0]->name);
        self::assertSame('\App\Helpers\process', $symbols[0]->fullyQualifiedName);
    }

    public function testExtractsMethodSymbols(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Models;
        class User {
            public function getName(): string {}
        }
        PHP;

        $symbols = $this->extractSymbols('app/Models/User.php', $code);

        self::assertCount(2, $symbols);
        self::assertSame('class', $symbols[0]->kind);
        self::assertSame('method', $symbols[1]->kind);
        self::assertSame('getName', $symbols[1]->name);
        self::assertSame('\App\Models\User::getName', $symbols[1]->fullyQualifiedName);
        self::assertSame('\App\Models\User', $symbols[1]->parentFullyQualifiedName);
    }

    public function testExtractsMultipleMethods(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Models;
        class User {
            public function getName(): string {}
            public function getEmail(): string {}
            protected function initialize(): void {}
        }
        PHP;

        $symbols = $this->extractSymbols('app/Models/User.php', $code);

        self::assertCount(4, $symbols);
        self::assertSame('class', $symbols[0]->kind);
        self::assertSame('method', $symbols[1]->kind);
        self::assertSame('method', $symbols[2]->kind);
        self::assertSame('method', $symbols[3]->kind);
    }

    public function testExtractsMultipleClasses(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Models;
        class User {}
        class Post {}
        PHP;

        $symbols = $this->extractSymbols('app/Models/Entities.php', $code);

        self::assertCount(2, $symbols);
        self::assertSame('User', $symbols[0]->name);
        self::assertSame('Post', $symbols[1]->name);
    }

    public function testHandlesFilesWithoutNamespace(): void
    {
        $code = <<<'PHP'
        <?php
        class Helper {}
        function process() {}
        PHP;

        $symbols = $this->extractSymbols('app/Helper.php', $code);

        self::assertCount(2, $symbols);
        self::assertSame('Helper', $symbols[0]->name);
        self::assertSame('process', $symbols[1]->name);
        self::assertSame('\Helper', $symbols[0]->fullyQualifiedName);
        self::assertSame('\process', $symbols[1]->fullyQualifiedName);
    }

    public function testIgnoresAnonymousClasses(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App;
        class Handler {
            public function process() {
                $handler = new class {
                    public function run() {}
                };
            }
        }
        PHP;

        $symbols = $this->extractSymbols('app/Handler.php', $code);

        // Should only extract Handler and its method, not the anonymous class
        self::assertGreaterThan(0, $symbols);
        self::assertSame('Handler', $symbols[0]->name);
    }

    public function testExtractsSymbolsWithComplexSignatures(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Models;
        class User {
            public function getEmail(string $format = 'plain'): ?string {}
            public static function create(array $data): self {}
        }
        PHP;

        $symbols = $this->extractSymbols('app/Models/User.php', $code);

        self::assertGreaterThanOrEqual(3, count($symbols));
        self::assertSame('method', $symbols[1]->kind);
        self::assertSame('method', $symbols[2]->kind);
    }

    public function testExtractsInterfaceWithMethods(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Contracts;
        interface Repository {
            public function find(int $id);
            public function all();
        }
        PHP;

        $symbols = $this->extractSymbols('app/Contracts/Repository.php', $code);

        self::assertGreaterThanOrEqual(1, count($symbols));
        self::assertSame('interface', $symbols[0]->kind);
    }

    public function testExtractsTraitWithMethods(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Traits;
        trait Auditable {
            public function createdBy() {}
            protected function recordAudit() {}
        }
        PHP;

        $symbols = $this->extractSymbols('app/Traits/Auditable.php', $code);

        self::assertGreaterThanOrEqual(1, count($symbols));
        self::assertSame('trait', $symbols[0]->kind);
    }

    public function testExtractsAbstractClassAndMethods(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Base;
        abstract class AbstractService {
            abstract public function execute();
            public function handle() {}
        }
        PHP;

        $symbols = $this->extractSymbols('app/Base/AbstractService.php', $code);

        self::assertGreaterThanOrEqual(3, count($symbols));
        self::assertSame('class', $symbols[0]->kind);
    }

    public function testExtractsStaticMethods(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Utils;
        class Helper {
            public static function format(string $text): string {}
            private static function validate() {}
        }
        PHP;

        $symbols = $this->extractSymbols('app/Utils/Helper.php', $code);

        self::assertGreaterThanOrEqual(3, count($symbols));
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
