<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Unit\Extraction\Visitors;

use Maarheeze\CodeGraph\Extraction\Visitors\InheritanceEdgeVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

use function count;

final class InheritanceVisitorMethodCoverageTest extends TestCase
{
    public function testAllInheritanceVisitorMethodsExecuted(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Domain\Models;

        use BaseModel;
        use Contracts\Auditable;

        interface Repository extends Contracts\Repository {
        }

        trait TimestampTrait {
        }

        enum Status {
            case Active;
        }

        class User extends BaseModel implements Auditable {
            use TimestampTrait;
        }

        class Admin extends User {
            use TimestampTrait;
        }
        PHP;

        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($code);

        $visitor = new InheritanceEdgeVisitor('test.php', $code);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);

        $edges = $visitor->edges();

        self::assertGreaterThan(0, count($edges));
        self::assertTrue($this->hasEdgeKind($edges, 'extends'));
        self::assertTrue($this->hasEdgeKind($edges, 'implements'));
        self::assertTrue($this->hasEdgeKind($edges, 'uses_trait'));
    }

    public function testInheritanceVisitorWithAnonymousClass(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App;

        class Service {
            public function create() {
                return new class extends Base {
                    use Trait1;
                };
            }
        }
        PHP;

        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($code);

        $visitor = new InheritanceEdgeVisitor('test.php', $code);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);

        $edges = $visitor->edges();

        self::assertGreaterThan(0, count($edges));
    }

    public function testInheritanceVisitorWithMultipleNamespaces(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\A;
        class UserA extends Base {
        }

        namespace App\B;
        class UserB extends Base {
        }
        PHP;

        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($code);

        $visitor = new InheritanceEdgeVisitor('test.php', $code);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);

        $edges = $visitor->edges();

        self::assertGreaterThanOrEqual(2, count($edges));
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
