<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Unit\Extraction\Visitors;

use Maarheeze\CodeGraph\Extraction\Visitors\SignatureBuilder;
use Maarheeze\CodeGraph\Extraction\Visitors\TypeFormatter;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

final class SignatureBuilderTest extends TestCase
{
    public function testBuildClassWithoutExtends(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        class User {}
        PHP;
        $statements = $parser->parse($code);
        $classNode = $statements[0];

        $result = $builder->buildClass($classNode);

        self::assertSame('class User', $result);
    }

    public function testBuildClassWithExtends(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        class Admin extends User {}
        PHP;
        $statements = $parser->parse($code);
        $classNode = $statements[0];

        $result = $builder->buildClass($classNode);

        self::assertSame('class Admin extends User', $result);
    }

    public function testBuildClassWithImplements(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        class UserService implements Service {}
        PHP;
        $statements = $parser->parse($code);
        $classNode = $statements[0];

        $result = $builder->buildClass($classNode);

        self::assertSame('class UserService implements Service', $result);
    }

    public function testBuildClassWithExtendsAndImplements(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        class Admin extends User implements Service, Auditable {}
        PHP;
        $statements = $parser->parse($code);
        $classNode = $statements[0];

        $result = $builder->buildClass($classNode);

        self::assertSame('class Admin extends User implements Service, Auditable', $result);
    }

    public function testBuildInterfaceWithoutExtends(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        interface Service {}
        PHP;
        $statements = $parser->parse($code);
        $interfaceNode = $statements[0];

        $result = $builder->buildInterface($interfaceNode);

        self::assertSame('interface Service', $result);
    }

    public function testBuildInterfaceWithExtends(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        interface Auditable extends Service {}
        PHP;
        $statements = $parser->parse($code);
        $interfaceNode = $statements[0];

        $result = $builder->buildInterface($interfaceNode);

        self::assertSame('interface Auditable extends Service', $result);
    }

    public function testBuildInterfaceWithMultipleExtends(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        interface Combined extends Service, Auditable {}
        PHP;
        $statements = $parser->parse($code);
        $interfaceNode = $statements[0];

        $result = $builder->buildInterface($interfaceNode);

        self::assertSame('interface Combined extends Service, Auditable', $result);
    }

    public function testBuildTrait(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        trait Timestamped {}
        PHP;
        $statements = $parser->parse($code);
        $traitNode = $statements[0];

        $result = $builder->buildTrait($traitNode);

        self::assertSame('trait Timestamped', $result);
    }

    public function testBuildEnumWithoutImplements(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        enum Status {}
        PHP;
        $statements = $parser->parse($code);
        $enumNode = $statements[0];

        $result = $builder->buildEnum($enumNode);

        self::assertSame('enum Status', $result);
    }

    public function testBuildEnumWithImplements(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        enum Status implements Stringable {}
        PHP;
        $statements = $parser->parse($code);
        $enumNode = $statements[0];

        $result = $builder->buildEnum($enumNode);

        self::assertSame('enum Status implements Stringable', $result);
    }

    public function testBuildFunctionWithoutParams(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        function process() {}
        PHP;
        $statements = $parser->parse($code);
        $functionNode = $statements[0];

        $result = $builder->buildFunction($functionNode);

        self::assertSame('function process()', $result);
    }

    public function testBuildFunctionWithParams(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        function greet(string $name, int $age) {}
        PHP;
        $statements = $parser->parse($code);
        $functionNode = $statements[0];

        $result = $builder->buildFunction($functionNode);

        self::assertSame('function greet(string $name, int $age)', $result);
    }

    public function testBuildFunctionWithReturnType(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        function getName(): string {}
        PHP;
        $statements = $parser->parse($code);
        $functionNode = $statements[0];

        $result = $builder->buildFunction($functionNode);

        self::assertSame('function getName(): string', $result);
    }

    public function testBuildFunctionWithParamsAndReturnType(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        function add(int $a, int $b): int {}
        PHP;
        $statements = $parser->parse($code);
        $functionNode = $statements[0];

        $result = $builder->buildFunction($functionNode);

        self::assertSame('function add(int $a, int $b): int', $result);
    }

    public function testBuildMethodPublic(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
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

        $result = $builder->buildMethod($methodNode, 'public', false);

        self::assertSame('public function getName(): string', $result);
    }

    public function testBuildMethodProtected(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        class User {
            protected function initialize() {}
        }
        PHP;
        $statements = $parser->parse($code);
        $classNode = $statements[0];
        $methodNode = $classNode->stmts[0];

        $result = $builder->buildMethod($methodNode, 'protected', false);

        self::assertSame('protected function initialize()', $result);
    }

    public function testBuildMethodStatic(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
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

        $result = $builder->buildMethod($methodNode, 'public', true);

        self::assertSame('public static function create(array $attributes): static', $result);
    }

    public function testBuildMethodWithMultipleParams(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        class User {
            public function update(string $name, ?int $age, array $data): bool {}
        }
        PHP;
        $statements = $parser->parse($code);
        $classNode = $statements[0];
        $methodNode = $classNode->stmts[0];

        $result = $builder->buildMethod($methodNode, 'public', false);

        self::assertSame('public function update(string $name, ?int $age, array $data): bool', $result);
    }

    public function testBuildMethodPrivate(): void
    {
        $builder = new SignatureBuilder(new TypeFormatter());
        $parser = $this->getParser();
        $code = <<<'PHP'
        <?php
        class User {
            private function internal() {}
        }
        PHP;
        $statements = $parser->parse($code);
        $classNode = $statements[0];
        $methodNode = $classNode->stmts[0];

        $result = $builder->buildMethod($methodNode, 'private', false);

        self::assertSame('private function internal()', $result);
    }

    private function getParser(): Parser
    {
        return (new ParserFactory())->createForHostVersion();
    }
}
