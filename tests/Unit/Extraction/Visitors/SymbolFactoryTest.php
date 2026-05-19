<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Unit\Extraction\Visitors;

use Maarheeze\CodeGraph\Extraction\Visitors\SignatureBuilder;
use Maarheeze\CodeGraph\Extraction\Visitors\SymbolFactory;
use Maarheeze\CodeGraph\Extraction\Visitors\TypeFormatter;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

final class SymbolFactoryTest extends TestCase
{
    public function testCreateClassWithoutAbstractFlag(): void
    {
        $factory = new SymbolFactory(new SignatureBuilder(new TypeFormatter()));
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        class User {}
        PHP;
        $statements = $parser->parse($code);
        $classNode = $statements[0];

        $symbol = $factory->createClass($classNode, 'App\Models\User', 'app/Models/User.php');

        self::assertSame('class', $symbol->kind);
        self::assertSame('User', $symbol->name);
        self::assertSame('App\Models\User', $symbol->fullyQualifiedName);
        self::assertNull($symbol->parentFullyQualifiedName);
        self::assertSame('app/Models/User.php', $symbol->file);
        self::assertFalse($symbol->isStatic);
        self::assertFalse($symbol->isAbstract);
        self::assertStringContainsString('class User', $symbol->signature);
    }

    public function testCreateClassWithAbstractFlag(): void
    {
        $factory = new SymbolFactory(new SignatureBuilder(new TypeFormatter()));
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        abstract class BaseModel {}
        PHP;
        $statements = $parser->parse($code);
        $classNode = $statements[0];

        $symbol = $factory->createClass($classNode, 'App\Models\BaseModel', 'app/Models/BaseModel.php');

        self::assertSame('class', $symbol->kind);
        self::assertSame('BaseModel', $symbol->name);
        self::assertTrue($symbol->isAbstract);
    }

    public function testCreateInterface(): void
    {
        $factory = new SymbolFactory(new SignatureBuilder(new TypeFormatter()));
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        interface Service {}
        PHP;
        $statements = $parser->parse($code);
        $interfaceNode = $statements[0];

        $symbol = $factory->createInterface($interfaceNode, 'App\Contracts\Service', 'app/Contracts/Service.php');

        self::assertSame('interface', $symbol->kind);
        self::assertSame('Service', $symbol->name);
        self::assertSame('App\Contracts\Service', $symbol->fullyQualifiedName);
        self::assertNull($symbol->parentFullyQualifiedName);
        self::assertSame('app/Contracts/Service.php', $symbol->file);
        self::assertFalse($symbol->isStatic);
        self::assertFalse($symbol->isAbstract);
        self::assertStringContainsString('interface Service', $symbol->signature);
    }

    public function testCreateTrait(): void
    {
        $factory = new SymbolFactory(new SignatureBuilder(new TypeFormatter()));
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        trait Timestamped {}
        PHP;
        $statements = $parser->parse($code);
        $traitNode = $statements[0];

        $symbol = $factory->createTrait($traitNode, 'App\Traits\Timestamped', 'app/Traits/Timestamped.php');

        self::assertSame('trait', $symbol->kind);
        self::assertSame('Timestamped', $symbol->name);
        self::assertSame('App\Traits\Timestamped', $symbol->fullyQualifiedName);
        self::assertNull($symbol->parentFullyQualifiedName);
        self::assertSame('app/Traits/Timestamped.php', $symbol->file);
        self::assertFalse($symbol->isStatic);
        self::assertFalse($symbol->isAbstract);
        self::assertStringContainsString('trait Timestamped', $symbol->signature);
    }

    public function testCreateEnum(): void
    {
        $factory = new SymbolFactory(new SignatureBuilder(new TypeFormatter()));
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        enum Status {}
        PHP;
        $statements = $parser->parse($code);
        $enumNode = $statements[0];

        $symbol = $factory->createEnum($enumNode, 'App\Enums\Status', 'app/Enums/Status.php');

        self::assertSame('enum', $symbol->kind);
        self::assertSame('Status', $symbol->name);
        self::assertSame('App\Enums\Status', $symbol->fullyQualifiedName);
        self::assertNull($symbol->parentFullyQualifiedName);
        self::assertSame('app/Enums/Status.php', $symbol->file);
        self::assertFalse($symbol->isStatic);
        self::assertFalse($symbol->isAbstract);
        self::assertStringContainsString('enum Status', $symbol->signature);
    }

    public function testCreateFunction(): void
    {
        $factory = new SymbolFactory(new SignatureBuilder(new TypeFormatter()));
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        function process(): void {}
        PHP;
        $statements = $parser->parse($code);
        $functionNode = $statements[0];

        $symbol = $factory->createFunction($functionNode, 'process', 'functions.php');

        self::assertSame('function', $symbol->kind);
        self::assertSame('process', $symbol->name);
        self::assertSame('process', $symbol->fullyQualifiedName);
        self::assertNull($symbol->parentFullyQualifiedName);
        self::assertSame('functions.php', $symbol->file);
        self::assertFalse($symbol->isStatic);
        self::assertFalse($symbol->isAbstract);
        self::assertStringContainsString('function process', $symbol->signature);
    }

    public function testCreateMethod(): void
    {
        $factory = new SymbolFactory(new SignatureBuilder(new TypeFormatter()));
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        class User {
            public function getName(): string {}
        }
        PHP;
        $statements = $parser->parse($code);
        $classNode = $statements[0];
        $methodNode = $classNode->stmts[0];

        $symbol = $factory->createMethod(
            $methodNode,
            'App\Models\User::getName',
            'App\Models\User',
            'app/Models/User.php',
            'public',
            false,
        );

        self::assertSame('method', $symbol->kind);
        self::assertSame('getName', $symbol->name);
        self::assertSame('App\Models\User::getName', $symbol->fullyQualifiedName);
        self::assertSame('App\Models\User', $symbol->parentFullyQualifiedName);
        self::assertSame('app/Models/User.php', $symbol->file);
        self::assertFalse($symbol->isStatic);
        self::assertFalse($symbol->isAbstract);
        self::assertSame('public', $symbol->visibility);
        self::assertStringContainsString('public function getName', $symbol->signature);
    }

    public function testCreateStaticMethod(): void
    {
        $factory = new SymbolFactory(new SignatureBuilder(new TypeFormatter()));
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        class User {
            public static function create(array $attributes): static {}
        }
        PHP;
        $statements = $parser->parse($code);
        $classNode = $statements[0];
        $methodNode = $classNode->stmts[0];

        $symbol = $factory->createMethod(
            $methodNode,
            'App\Models\User::create',
            'App\Models\User',
            'app/Models/User.php',
            'public',
            true,
        );

        self::assertSame('method', $symbol->kind);
        self::assertSame('create', $symbol->name);
        self::assertTrue($symbol->isStatic);
        self::assertFalse($symbol->isAbstract);
        self::assertStringContainsString('public static function create', $symbol->signature);
    }

    public function testCreateAbstractMethod(): void
    {
        $factory = new SymbolFactory(new SignatureBuilder(new TypeFormatter()));
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        abstract class Handler {
            abstract public function handle(mixed $data): void;
        }
        PHP;
        $statements = $parser->parse($code);
        $classNode = $statements[0];
        $methodNode = $classNode->stmts[0];

        $symbol = $factory->createMethod(
            $methodNode,
            'App\Handlers\Handler::handle',
            'App\Handlers\Handler',
            'app/Handlers/Handler.php',
            'public',
            false,
        );

        self::assertSame('method', $symbol->kind);
        self::assertSame('handle', $symbol->name);
        self::assertTrue($symbol->isAbstract);
    }

    private function getParser(): Parser
    {
        return (new ParserFactory())->createForHostVersion();
    }
}
