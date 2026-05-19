<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Extraction\Visitors;

use Maarheeze\CodeGraph\Extraction\BaseAstVisitor;
use Maarheeze\CodeGraph\Values\Edge;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;

use function sprintf;

final class InheritanceEdgeVisitor extends BaseAstVisitor
{
    private string $currentNamespace = '';
    private ?string $currentClassFqn = null;

    public function enterNode(Node $node): null
    {
        match (true) {
            $node instanceof Namespace_ => $this->handleNamespace($node),
            $node instanceof Class_ => $this->handleClass($node),
            $node instanceof Interface_ => $this->handleInterface($node),
            $node instanceof Trait_ => $this->handleTrait($node),
            $node instanceof Enum_ => $this->handleEnum($node),
            $node instanceof TraitUse => $this->handleTraitUse($node),
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

        if ($node->extends !== null) {
            $this->edgesList[] = new Edge(
                'extends',
                $fqn,
                $node->extends->toString(),
                $this->relativeFilePath,
                $node->extends->getStartLine(),
            );
        }

        foreach ($node->implements as $implement) {
            $this->edgesList[] = new Edge(
                'implements',
                $fqn,
                $implement->toString(),
                $this->relativeFilePath,
                $implement->getStartLine(),
            );
        }
    }

    private function handleInterface(Interface_ $node): void
    {
        if ($node->name === null) {
            return;
        }

        $fqn = $this->buildFqn($node->name->name);
        $this->currentClassFqn = $fqn;

        foreach ($node->extends as $extend) {
            $this->edgesList[] = new Edge(
                'extends',
                $fqn,
                $extend->toString(),
                $this->relativeFilePath,
                $extend->getStartLine(),
            );
        }
    }

    private function handleTrait(Trait_ $node): void
    {
        if ($node->name === null) {
            return;
        }

        $fqn = $this->buildFqn($node->name->name);
        $this->currentClassFqn = $fqn;
    }

    private function handleEnum(Enum_ $node): void
    {
        if ($node->name === null) {
            return;
        }

        $fqn = $this->buildFqn($node->name->name);
        $this->currentClassFqn = $fqn;

        foreach ($node->implements as $implement) {
            $this->edgesList[] = new Edge(
                'implements',
                $fqn,
                $implement->toString(),
                $this->relativeFilePath,
                $implement->getStartLine(),
            );
        }
    }

    private function handleTraitUse(TraitUse $node): void
    {
        if ($this->currentClassFqn === null) {
            return;
        }

        foreach ($node->traits as $trait) {
            $this->edgesList[] = new Edge(
                'uses_trait',
                $this->currentClassFqn,
                $trait->toString(),
                $this->relativeFilePath,
                $trait->getStartLine(),
            );
        }
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
