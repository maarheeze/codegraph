<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Unit\Extraction\Visitors;

use Maarheeze\CodeGraph\Extraction\Visitors\CallEdgeVisitor;
use Maarheeze\CodeGraph\Extraction\Visitors\InheritanceEdgeVisitor;
use Maarheeze\CodeGraph\Extraction\Visitors\SignatureBuilder;
use Maarheeze\CodeGraph\Extraction\Visitors\SymbolFactory;
use Maarheeze\CodeGraph\Extraction\Visitors\SymbolVisitor;
use Maarheeze\CodeGraph\Extraction\Visitors\TypeFormatter;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

use function count;

final class ComplexCodeVisitorTest extends TestCase
{
    public function testVisitorsHandleComplexPhpCode(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Services;

        use App\Models\User;
        use App\Contracts\Repository;
        use App\Traits\Auditable;

        interface UserRepositoryContract extends Repository {
            public function findByEmail(string $email): ?User;
        }

        class UserService implements UserRepositoryContract {
            use Auditable;

            public function findByEmail(string $email): ?User {
                $repo = new UserRepository();
                return $repo->findByEmail($email);
            }

            public function create(array $data): User {
                return User::create($data);
            }

            protected function log(string $message): void {
                echo $message;
            }
        }

        abstract class BaseService {
            abstract public function execute();
        }

        trait Auditable {
            public function audit(): void {
                log('auditing');
            }
        }
        PHP;

        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($code);

        $typeFormatter = new TypeFormatter();
        $signatureBuilder = new SignatureBuilder($typeFormatter);
        $symbolFactory = new SymbolFactory($signatureBuilder);

        $symbolVisitor = new SymbolVisitor('test.php', $code, $symbolFactory);
        $inheritanceVisitor = new InheritanceEdgeVisitor('test.php', $code);
        $callVisitor = new CallEdgeVisitor('test.php', $code);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($symbolVisitor);
        $traverser->addVisitor($inheritanceVisitor);
        $traverser->addVisitor($callVisitor);

        $traverser->traverse($statements);

        $symbols = $symbolVisitor->symbols();
        $inheritanceEdges = $inheritanceVisitor->edges();
        $callEdges = $callVisitor->edges();

        self::assertGreaterThan(0, count($symbols));
        self::assertGreaterThan(0, count($inheritanceEdges));
        self::assertGreaterThan(0, count($callEdges));
    }

    public function testVisitorsWithNestedStructures(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Commands;

        class Command {
            public function handle(): void {
                $service = new Service();
                $result = $service->execute();
                $this->process($result);
            }

            private function process($data): void {
                foreach ($data as $item) {
                    $this->handle($item);
                }
            }
        }

        trait CommandTrait {
            public function boot(): void {
                $this->register();
            }

            abstract protected function register(): void;
        }
        PHP;

        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($code);

        $typeFormatter = new TypeFormatter();
        $signatureBuilder = new SignatureBuilder($typeFormatter);
        $symbolFactory = new SymbolFactory($signatureBuilder);

        $symbolVisitor = new SymbolVisitor('test.php', $code, $symbolFactory);
        $callVisitor = new CallEdgeVisitor('test.php', $code);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($symbolVisitor);
        $traverser->addVisitor($callVisitor);

        $traverser->traverse($statements);

        $symbols = $symbolVisitor->symbols();
        $callEdges = $callVisitor->edges();

        self::assertGreaterThan(0, count($symbols));
        self::assertGreaterThan(0, count($callEdges));
    }
}
