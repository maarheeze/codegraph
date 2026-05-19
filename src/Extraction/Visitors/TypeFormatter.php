<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Extraction\Visitors;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use RuntimeException;

use function implode;
use function sprintf;

final class TypeFormatter
{
    public function format(Node $type): string
    {
        return match (true) {
            $type instanceof NullableType => sprintf('?%s', $this->format($type->type)),
            $type instanceof UnionType => $this->formatUnion($type),
            $type instanceof IntersectionType => $this->formatIntersection($type),
            $type instanceof Identifier,
            $type instanceof Name => $type->toString(),
            default => throw new RuntimeException('Unexpected type in format'),
        };
    }

    private function formatIntersection(IntersectionType $type): string
    {
        $parts = [];
        foreach ($type->types as $t) {
            $parts[] = $t->toString();
        }

        return implode('&', $parts);
    }

    private function formatUnion(UnionType $type): string
    {
        $parts = [];
        foreach ($type->types as $t) {
            $parts[] = $this->format($t);
        }

        return implode('|', $parts);
    }
}
