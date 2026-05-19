<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Extraction\Visitors;

use Maarheeze\CodeGraph\Extraction\BaseAstVisitor;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;

use function sprintf;

final class SymbolVisitor extends BaseAstVisitor
{
    private string $currentNamespace = '';
    private ?string $currentClassFqn = null;

    public function __construct(
        string $relativeFilePath,
        string $sourceContents,
        private readonly SymbolFactory $symbolFactory,
    ) {
        parent::__construct($relativeFilePath, $sourceContents);
    }

    public function enterNode(Node $node): null
    {
        match (true) {
            $node instanceof Namespace_ => $this->handleNamespace($node),
            $node instanceof Class_ => $this->handleClass($node),
            $node instanceof Interface_ => $this->handleInterface($node),
            $node instanceof Trait_ => $this->handleTrait($node),
            $node instanceof Enum_ => $this->handleEnum($node),
            $node instanceof Function_ => $this->handleFunction($node),
            $node instanceof ClassMethod => $this->handleClassMethod($node),
            default => null,
        };

        return null;
    }

    private function handleNamespace(Namespace_ $node): void
    {
        $this->currentNamespace = $node->name ? $node->name->toString() : '';
    }

    private function handleClass(Class_ $node): void
    {
        if ($node->name === null) {
            return;
        }

        $fqn = $this->buildFqn($node->name->name);
        $this->currentClassFqn = $fqn;

        $this->symbolsList[] = $this->symbolFactory->createClass(
            $node,
            $fqn,
            $this->relativeFilePath,
        );
    }

    private function handleInterface(Interface_ $node): void
    {
        if ($node->name === null) {
            return;
        }

        $fqn = $this->buildFqn($node->name->name);

        $this->symbolsList[] = $this->symbolFactory->createInterface(
            $node,
            $fqn,
            $this->relativeFilePath,
        );
    }

    private function handleTrait(Trait_ $node): void
    {
        if ($node->name === null) {
            return;
        }

        $fqn = $this->buildFqn($node->name->name);

        $this->symbolsList[] = $this->symbolFactory->createTrait(
            $node,
            $fqn,
            $this->relativeFilePath,
        );
    }

    private function handleEnum(Enum_ $node): void
    {
        if ($node->name === null) {
            return;
        }

        $fqn = $this->buildFqn($node->name->name);

        $this->symbolsList[] = $this->symbolFactory->createEnum(
            $node,
            $fqn,
            $this->relativeFilePath,
        );
    }

    private function handleFunction(Function_ $node): void
    {
        $fqn = $this->buildFqn($node->name->name);

        $this->symbolsList[] = $this->symbolFactory->createFunction(
            $node,
            $fqn,
            $this->relativeFilePath,
        );
    }

    private function handleClassMethod(ClassMethod $node): void
    {
        if ($this->currentClassFqn === null) {
            return;
        }

        $methodFqn = sprintf('%s::%s', $this->currentClassFqn, $node->name->name);
        $visibility = $this->determineMethodVisibility($node);

        $this->symbolsList[] = $this->symbolFactory->createMethod(
            $node,
            $methodFqn,
            $this->currentClassFqn,
            $this->relativeFilePath,
            $visibility,
            $node->isStatic(),
        );
    }

    private function determineMethodVisibility(ClassMethod $node): string
    {
        return match (true) {
            $node->isPrivate() => 'private',
            $node->isProtected() => 'protected',
            default => 'public',
        };
    }

    public function leaveNode(Node $node): null
    {
        if (
            $node instanceof Class_
            || $node instanceof Interface_
            || $node instanceof Trait_
            || $node instanceof Enum_
        ) {
            $this->currentClassFqn = null;

            return null;
        }

        return null;
    }

    private function buildFqn(string $name): string
    {
        if ($this->currentNamespace === '') {
            return sprintf('\\%s', $name);
        }

        return sprintf('\\%s\\%s', $this->currentNamespace, $name);
    }
}
