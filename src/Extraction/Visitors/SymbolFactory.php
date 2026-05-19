<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Extraction\Visitors;

use Maarheeze\CodeGraph\Values\Symbol;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use Webmozart\Assert\Assert;

final readonly class SymbolFactory
{
    public function __construct(
        private SignatureBuilder $signatureBuilder,
    ) {
    }

    public function createClass(
        Class_ $node,
        string $fqn,
        string $relPath,
    ): Symbol {
        Assert::notNull($node->name);
        $signature = $this->signatureBuilder->buildClass($node);
        $isAbstract = $node->isAbstract();

        return new Symbol(
            'class',
            $node->name->name,
            $fqn,
            null,
            $relPath,
            $node->getStartLine(),
            $node->getEndLine(),
            '',
            false,
            $isAbstract,
            $signature,
            $node->getDocComment()?->getText(),
        );
    }

    public function createInterface(
        Interface_ $node,
        string $fqn,
        string $relPath,
    ): Symbol {
        Assert::notNull($node->name);
        $signature = $this->signatureBuilder->buildInterface($node);

        return new Symbol(
            'interface',
            $node->name->name,
            $fqn,
            null,
            $relPath,
            $node->getStartLine(),
            $node->getEndLine(),
            '',
            false,
            false,
            $signature,
            $node->getDocComment()?->getText(),
        );
    }

    public function createTrait(
        Trait_ $node,
        string $fqn,
        string $relPath,
    ): Symbol {
        Assert::notNull($node->name);
        $signature = $this->signatureBuilder->buildTrait($node);

        return new Symbol(
            'trait',
            $node->name->name,
            $fqn,
            null,
            $relPath,
            $node->getStartLine(),
            $node->getEndLine(),
            '',
            false,
            false,
            $signature,
            $node->getDocComment()?->getText(),
        );
    }

    public function createEnum(
        Enum_ $node,
        string $fqn,
        string $relPath,
    ): Symbol {
        Assert::notNull($node->name);
        $signature = $this->signatureBuilder->buildEnum($node);

        return new Symbol(
            'enum',
            $node->name->name,
            $fqn,
            null,
            $relPath,
            $node->getStartLine(),
            $node->getEndLine(),
            '',
            false,
            false,
            $signature,
            $node->getDocComment()?->getText(),
        );
    }

    public function createFunction(
        Function_ $node,
        string $fqn,
        string $relPath,
    ): Symbol {
        $signature = $this->signatureBuilder->buildFunction($node);

        return new Symbol(
            'function',
            $node->name->name,
            $fqn,
            null,
            $relPath,
            $node->getStartLine(),
            $node->getEndLine(),
            '',
            false,
            false,
            $signature,
            $node->getDocComment()?->getText(),
        );
    }

    public function createMethod(
        ClassMethod $node,
        string $methodFqn,
        string $classFqn,
        string $relPath,
        string $visibility,
        bool $isStatic,
    ): Symbol {
        $isAbstract = $node->isAbstract();
        $signature = $this->signatureBuilder->buildMethod($node, $visibility, $isStatic);

        return new Symbol(
            'method',
            $node->name->name,
            $methodFqn,
            $classFqn,
            $relPath,
            $node->getStartLine(),
            $node->getEndLine(),
            $visibility,
            $isStatic,
            $isAbstract,
            $signature,
            $node->getDocComment()?->getText(),
        );
    }
}
