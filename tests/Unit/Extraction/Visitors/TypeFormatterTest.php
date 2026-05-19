<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Unit\Extraction\Visitors;

use Maarheeze\CodeGraph\Extraction\Visitors\TypeFormatter;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use PHPUnit\Framework\TestCase;

final class TypeFormatterTest extends TestCase
{
    private TypeFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new TypeFormatter();
    }

    public function testFormatIdentifier(): void
    {
        $identifier = new Identifier('string');

        $result = $this->formatter->format($identifier);

        self::assertSame('string', $result);
    }

    public function testFormatName(): void
    {
        $name = new Name('Illuminate\Database\Model');

        $result = $this->formatter->format($name);

        self::assertSame('Illuminate\Database\Model', $result);
    }

    public function testFormatNullableType(): void
    {
        $nullable = new NullableType(new Identifier('string'));

        $result = $this->formatter->format($nullable);

        self::assertSame('?string', $result);
    }

    public function testFormatNullableWithName(): void
    {
        $nullable = new NullableType(new Name('App\Models\User'));

        $result = $this->formatter->format($nullable);

        self::assertSame('?App\Models\User', $result);
    }

    public function testFormatUnionType(): void
    {
        $union = new UnionType([
            new Identifier('string'),
            new Identifier('int'),
        ]);

        $result = $this->formatter->format($union);

        self::assertSame('string|int', $result);
    }

    public function testFormatUnionWithThreeTypes(): void
    {
        $union = new UnionType([
            new Identifier('string'),
            new Identifier('int'),
            new Identifier('null'),
        ]);

        $result = $this->formatter->format($union);

        self::assertSame('string|int|null', $result);
    }

    public function testFormatUnionWithNames(): void
    {
        $union = new UnionType([
            new Name('App\Models\User'),
            new Name('App\Models\Guest'),
        ]);

        $result = $this->formatter->format($union);

        self::assertSame('App\Models\User|App\Models\Guest', $result);
    }

    public function testFormatNullableUnion(): void
    {
        $union = new UnionType([
            new Identifier('string'),
            new Identifier('int'),
        ]);
        $nullable = new NullableType($union);

        $result = $this->formatter->format($nullable);

        self::assertSame('?string|int', $result);
    }

    public function testFormatIntersectionType(): void
    {
        $intersection = new IntersectionType([
            new Name('Countable'),
            new Name('ArrayAccess'),
        ]);

        $result = $this->formatter->format($intersection);

        self::assertSame('Countable&ArrayAccess', $result);
    }

    public function testFormatIntersectionWithThreeTypes(): void
    {
        $intersection = new IntersectionType([
            new Name('Countable'),
            new Name('ArrayAccess'),
            new Name('Iterator'),
        ]);

        $result = $this->formatter->format($intersection);

        self::assertSame('Countable&ArrayAccess&Iterator', $result);
    }

    public function testFormatComplexNullableIntersection(): void
    {
        $intersection = new IntersectionType([
            new Name('Iterator'),
            new Name('Countable'),
        ]);
        $nullable = new NullableType($intersection);

        $result = $this->formatter->format($nullable);

        self::assertSame('?Iterator&Countable', $result);
    }
}
