<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Unit\Extraction\Visitors;

use Maarheeze\CodeGraph\Extraction\Visitors\InheritanceEdgeVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

final class InheritanceEdgeVisitorTest extends TestCase
{
    public function testExtractsClassExtendsEdge(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Models;
        class Admin extends User {}
        PHP;

        $edges = $this->extractEdges('app/Models/Admin.php', $code);

        self::assertCount(1, $edges);
        self::assertSame('extends', $edges[0]->kind);
        self::assertSame('\App\Models\Admin', $edges[0]->sourceFullyQualifiedName);
        self::assertSame('User', $edges[0]->destinationFullyQualifiedName);
    }

    public function testExtractsClassImplementsEdge(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Services;
        class UserService implements Service {}
        PHP;

        $edges = $this->extractEdges('app/Services/UserService.php', $code);

        self::assertCount(1, $edges);
        self::assertSame('implements', $edges[0]->kind);
        self::assertSame('\App\Services\UserService', $edges[0]->sourceFullyQualifiedName);
        self::assertSame('Service', $edges[0]->destinationFullyQualifiedName);
    }

    public function testExtractsMultipleImplementsEdges(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Services;
        class Handler implements Service, Auditable {}
        PHP;

        $edges = $this->extractEdges('app/Services/Handler.php', $code);

        self::assertCount(2, $edges);
        self::assertSame('implements', $edges[0]->kind);
        self::assertSame('implements', $edges[1]->kind);
    }

    public function testExtractsExtendsAndImplementsEdges(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Models;
        class Admin extends User implements Auditable {}
        PHP;

        $edges = $this->extractEdges('app/Models/Admin.php', $code);

        self::assertCount(2, $edges);
        self::assertSame('extends', $edges[0]->kind);
        self::assertSame('implements', $edges[1]->kind);
    }

    public function testExtractsInterfaceExtendsEdge(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Contracts;
        interface Auditable extends Service {}
        PHP;

        $edges = $this->extractEdges('app/Contracts/Auditable.php', $code);

        self::assertCount(1, $edges);
        self::assertSame('extends', $edges[0]->kind);
        self::assertSame('\App\Contracts\Auditable', $edges[0]->sourceFullyQualifiedName);
        self::assertSame('Service', $edges[0]->destinationFullyQualifiedName);
    }

    public function testExtractsMultipleInterfaceExtendsEdges(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Contracts;
        interface Combined extends Service, Auditable {}
        PHP;

        $edges = $this->extractEdges('app/Contracts/Combined.php', $code);

        self::assertCount(2, $edges);
    }

    public function testExtractsTraitUseEdge(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Models;
        class User {
            use Timestamped;
        }
        PHP;

        $edges = $this->extractEdges('app/Models/User.php', $code);

        self::assertCount(1, $edges);
        self::assertSame('uses_trait', $edges[0]->kind);
        self::assertSame('\App\Models\User', $edges[0]->sourceFullyQualifiedName);
        self::assertSame('Timestamped', $edges[0]->destinationFullyQualifiedName);
    }

    public function testExtractsMultipleTraitUseEdges(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Models;
        class User {
            use Timestamped, SoftDeletes;
        }
        PHP;

        $edges = $this->extractEdges('app/Models/User.php', $code);

        self::assertCount(2, $edges);
        self::assertSame('uses_trait', $edges[0]->kind);
        self::assertSame('uses_trait', $edges[1]->kind);
    }

    public function testExtractsEnumImplementsEdge(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Enums;
        enum Status implements Stringable {}
        PHP;

        $edges = $this->extractEdges('app/Enums/Status.php', $code);

        self::assertCount(1, $edges);
        self::assertSame('implements', $edges[0]->kind);
        self::assertSame('\App\Enums\Status', $edges[0]->sourceFullyQualifiedName);
        self::assertSame('Stringable', $edges[0]->destinationFullyQualifiedName);
    }

    public function testExtractsEdgesFromMultipleClasses(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Models;
        class User extends Model implements Auditable {
            use Timestamped;
        }
        class Post extends Model {
            use Timestamped;
        }
        PHP;

        $edges = $this->extractEdges('app/Models/Entities.php', $code);

        // User: extends Model, implements Auditable, uses Timestamped = 3 edges
        // Post: extends Model, uses Timestamped = 2 edges
        self::assertCount(5, $edges);
    }

    public function testIgnoresClassesWithoutInheritance(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Models;
        class Standalone {}
        PHP;

        $edges = $this->extractEdges('app/Models/Standalone.php', $code);

        self::assertCount(0, $edges);
    }

    public function testHandlesFullyQualifiedNames(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Models;
        class User extends \Illuminate\Database\Eloquent\Model {}
        PHP;

        $edges = $this->extractEdges('app/Models/User.php', $code);

        self::assertCount(1, $edges);
        self::assertSame('\App\Models\User', $edges[0]->sourceFullyQualifiedName);
        self::assertSame('Illuminate\Database\Eloquent\Model', $edges[0]->destinationFullyQualifiedName);
    }

    public function testExtractsTraitEdgesWithNamespace(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Traits;
        trait Helper {
            use AnotherTrait;
        }
        PHP;

        $edges = $this->extractEdges('app/Traits/Helper.php', $code);

        self::assertCount(1, $edges);
        self::assertSame('uses_trait', $edges[0]->kind);
    }

    public function testHandlesGlobalNamespaceClass(): void
    {
        $code = <<<'PHP'
        <?php
        class GlobalClass extends BaseClass {}
        PHP;

        $edges = $this->extractEdges('GlobalClass.php', $code);

        self::assertCount(1, $edges);
        self::assertSame('\GlobalClass', $edges[0]->sourceFullyQualifiedName);
    }

    public function testHandlesMultipleTraitsInClass(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App\Models;
        class User {
            use Timestamped, SoftDeletes, UuidTrait;
        }
        PHP;

        $edges = $this->extractEdges('app/Models/User.php', $code);

        self::assertCount(3, $edges);
        self::assertTrue(true);
    }

    public function testIgnoresEdgeFromGlobalNamespace(): void
    {
        $code = <<<'PHP'
        <?php
        class User extends Model {}
        PHP;

        $edges = $this->extractEdges('User.php', $code);

        self::assertCount(1, $edges);
    }

    public function testExtractsEdgesWithQualifiedNames(): void
    {
        $code = <<<'PHP'
        <?php
        class User extends \Database\Model implements \Contracts\Auditable {}
        PHP;

        $edges = $this->extractEdges('User.php', $code);

        self::assertCount(2, $edges);
        self::assertSame('extends', $edges[0]->kind);
        self::assertSame('implements', $edges[1]->kind);
    }

    public function testHandlesTraitWithoutClassContext(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App;
        trait Loggable {
            use Another;
        }
        PHP;

        $edges = $this->extractEdges('app/Traits/Loggable.php', $code);

        self::assertCount(1, $edges);
        self::assertSame('uses_trait', $edges[0]->kind);
    }

    /**
     * @return array<int, mixed>
     */
    private function extractEdges(string $filePath, string $code): array
    {
        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($code);

        $visitor = new InheritanceEdgeVisitor($filePath, $code);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);

        return $visitor->edges();
    }
}
