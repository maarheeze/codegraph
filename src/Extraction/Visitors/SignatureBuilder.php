<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Extraction\Visitors;

use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use Webmozart\Assert\Assert;

use function count;
use function implode;
use function is_string;
use function sprintf;

final readonly class SignatureBuilder
{
    public function __construct(
        private TypeFormatter $typeFormatter,
    ) {
    }

    public function buildClass(Class_ $node): string
    {
        Assert::notNull($node->name);
        $signature = sprintf('class %s', $node->name->name);

        if ($node->extends !== null) {
            $signature .= sprintf(' extends %s', $node->extends->toString());
        }

        if (count($node->implements) > 0) {
            $implements = [];

            foreach ($node->implements as $implement) {
                $implements[] = $implement->toString();
            }

            $signature .= sprintf(' implements %s', implode(', ', $implements));
        }

        return $signature;
    }

    public function buildInterface(Interface_ $node): string
    {
        Assert::notNull($node->name);
        $signature = sprintf('interface %s', $node->name->name);

        if (count($node->extends) > 0) {
            $extends = [];

            foreach ($node->extends as $extend) {
                $extends[] = $extend->toString();
            }

            $signature .= sprintf(' extends %s', implode(', ', $extends));
        }

        return $signature;
    }

    public function buildTrait(Trait_ $node): string
    {
        Assert::isInstanceOf($node->name, Identifier::class);
        return sprintf('trait %s', $node->name->name);
    }

    public function buildEnum(Enum_ $node): string
    {
        Assert::isInstanceOf($node->name, Identifier::class);
        $signature = sprintf('enum %s', $node->name->name);

        if (count($node->implements) > 0) {
            $implements = [];

            foreach ($node->implements as $implement) {
                $implements[] = $implement->toString();
            }

            $signature .= sprintf(' implements %s', implode(', ', $implements));
        }

        return $signature;
    }

    public function buildFunction(Function_ $node): string
    {
        Assert::notNull($node->name);
        $signature = sprintf('function %s(', $node->name->name);
        Assert::isArray($node->params);
        $params = $this->buildParams($node->params);
        $signature .= sprintf('%s)', implode(', ', $params));

        if ($node->returnType !== null) {
            $signature .= sprintf(': %s', $this->typeFormatter->format($node->returnType));
        }

        return $signature;
    }

    public function buildMethod(ClassMethod $node, string $visibility, bool $isStatic): string
    {
        Assert::notNull($node->name);
        $signature = sprintf('%s%s function %s(', $visibility, $isStatic ? ' static' : '', $node->name->name);
        Assert::isArray($node->params);
        $params = $this->buildParams($node->params);
        $signature .= sprintf('%s)', implode(', ', $params));

        if ($node->returnType !== null) {
            $signature .= sprintf(': %s', $this->typeFormatter->format($node->returnType));
        }

        return $signature;
    }

    /**
     * @param array<string|int, Param> $params
     * @return array<int, string>
     */
    private function buildParams(array $params): array
    {
        $result = [];

        foreach ($params as $param) {
            if (!($param->var instanceof Variable)) {
                continue;
            }

            if (!is_string($param->var->name)) {
                continue;
            }

            $paramStr = '';

            if ($param->type !== null) {
                $paramStr = sprintf('%s ', $this->typeFormatter->format($param->type));
            }

            $paramStr .= sprintf('$%s', $param->var->name);
            $result[] = $paramStr;
        }

        return $result;
    }
}
