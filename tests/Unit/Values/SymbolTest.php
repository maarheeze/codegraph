<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Unit\Values;

use Maarheeze\CodeGraph\Tests\TestCase;
use Maarheeze\CodeGraph\Values\Symbol;

final class SymbolTest extends TestCase
{
    public function testSymbolCanBeConstructed(): void
    {
        $symbol = new Symbol(
            'method',
            'create',
            'App\Models\User::create',
            'App\Models\User',
            'app/Models/User.php',
            42,
            50,
            'public',
            true,
            false,
            'public static function create(array $attributes = []): static',
            '/** @param array $attributes User attributes */',
        );

        self::assertSame('method', $symbol->kind);
        self::assertSame('create', $symbol->name);
        self::assertSame('App\Models\User::create', $symbol->fullyQualifiedName);
        self::assertSame('App\Models\User', $symbol->parentFullyQualifiedName);
        self::assertSame('app/Models/User.php', $symbol->file);
        self::assertSame(42, $symbol->startLine);
        self::assertSame(50, $symbol->endLine);
        self::assertSame('public', $symbol->visibility);
        self::assertTrue($symbol->isStatic);
        self::assertFalse($symbol->isAbstract);
    }

    public function testSymbolWithNullParentFqn(): void
    {
        $symbol = new Symbol(
            'class',
            'User',
            'App\Models\User',
            null,
            'app/Models/User.php',
            10,
            200,
            'public',
            false,
            false,
            'class User',
            null,
        );

        self::assertNull($symbol->parentFullyQualifiedName);
        self::assertNull($symbol->docblock);
    }

    public function testSymbolPropertiesAreAccessible(): void
    {
        $symbol = new Symbol(
            'function',
            'dd',
            'dd',
            null,
            'vendor/laravel/framework/src/Illuminate/Support/helpers.php',
            1,
            10,
            'public',
            false,
            false,
            'function dd(...$args): never',
            null,
        );

        self::assertSame('function', $symbol->kind);
        self::assertSame('dd', $symbol->name);
        self::assertSame('dd', $symbol->fullyQualifiedName);
    }
}
