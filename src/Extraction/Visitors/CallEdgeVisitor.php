<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Extraction\Visitors;

use Maarheeze\CodeGraph\Extraction\BaseAstVisitor;
use Maarheeze\CodeGraph\Values\Edge;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;

use function sprintf;

final class CallEdgeVisitor extends BaseAstVisitor
{
    private string $currentNamespace = '';
    private ?string $currentClassFqn = null;
    private ?string $currentMethodName = null;

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
            $node instanceof MethodCall => $this->handleMethodCall($node),
            $node instanceof StaticCall => $this->handleStaticCall($node),
            $node instanceof New_ => $this->handleNew($node),
            $node instanceof FuncCall => $this->handleFuncCall($node),
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

        $this->currentClassFqn = $this->buildFqn($node->name->name);
    }

    private function handleInterface(Interface_ $node): void
    {
        if ($node->name === null) {
            return;
        }

        $this->currentClassFqn = $this->buildFqn($node->name->name);
    }

    private function handleTrait(Trait_ $node): void
    {
        if ($node->name === null) {
            return;
        }

        $this->currentClassFqn = $this->buildFqn($node->name->name);
    }

    private function handleEnum(Enum_ $node): void
    {
        if ($node->name === null) {
            return;
        }

        $this->currentClassFqn = $this->buildFqn($node->name->name);
    }

    private function handleFunction(Function_ $node): void
    {
        $this->currentClassFqn = null;
        $this->currentMethodName = $node->name->name;
    }

    private function handleClassMethod(ClassMethod $node): void
    {
        $this->currentMethodName = $node->name->name;
    }

    private function handleMethodCall(MethodCall $node): void
    {
        $sourceFullyQualifiedName = $this->getCurrentCallContext();

        if ($sourceFullyQualifiedName === null) {
            return;
        }

        $methodName = $node->name instanceof Node\Identifier ? $node->name->toString() : '?';

        $this->edgesList[] = new Edge(
            'calls',
            $sourceFullyQualifiedName,
            sprintf('?::%s', $methodName),
            $this->relativeFilePath,
            $node->getStartLine(),
        );
    }

    private function handleStaticCall(StaticCall $node): void
    {
        $sourceFullyQualifiedName = $this->getCurrentCallContext();

        if ($sourceFullyQualifiedName === null) {
            return;
        }

        $className = '';

        if ($node->class instanceof Name) {
            $className = $node->class->toString();
        }

        $methodName = $node->name instanceof Node\Identifier ? $node->name->toString() : '?';

        $this->edgesList[] = new Edge(
            'calls',
            $sourceFullyQualifiedName,
            sprintf('%s::%s', $className, $methodName),
            $this->relativeFilePath,
            $node->getStartLine(),
        );
    }

    private function handleNew(New_ $node): void
    {
        $sourceFullyQualifiedName = $this->getCurrentCallContext();

        if ($sourceFullyQualifiedName === null) {
            return;
        }

        $className = '';

        if ($node->class instanceof Name) {
            $className = $node->class->toString();
        }

        $this->edgesList[] = new Edge(
            'instantiates',
            $sourceFullyQualifiedName,
            $className,
            $this->relativeFilePath,
            $node->getStartLine(),
        );
    }

    private function handleFuncCall(FuncCall $node): void
    {
        $sourceFullyQualifiedName = $this->getCurrentCallContext();

        if ($sourceFullyQualifiedName === null) {
            return;
        }

        if ($node->name instanceof Name) {
            $functionName = $node->name->toString();

            $this->edgesList[] = new Edge(
                'calls',
                $sourceFullyQualifiedName,
                $functionName,
                $this->relativeFilePath,
                $node->getStartLine(),
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
            $this->currentMethodName = null;

            return null;
        }

        if ($node instanceof Function_ || $node instanceof ClassMethod) {
            $this->currentMethodName = null;

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

    private function getCurrentCallContext(): ?string
    {
        if ($this->currentClassFqn !== null && $this->currentMethodName !== null) {
            return sprintf('%s::%s', $this->currentClassFqn, $this->currentMethodName);
        }

        if ($this->currentMethodName !== null) {
            return $this->buildFqn($this->currentMethodName);
        }

        return null;
    }
}
