<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Mcp;

use function array_key_exists;
use function is_array;
use function is_string;
use function json_decode;

final readonly class McpRequestParser
{
    /**
     * @return ?array<string, mixed>
     */
    public static function parse(string $line): ?array
    {
        $decoded = json_decode($line, true);

        if (!is_array($decoded)) {
            return null;
        }

        $request = [];

        foreach ($decoded as $key => $value) {
            if (!is_string($key)) {
                return null;
            }

            $request[$key] = $value;
        }

        return $request;
    }

    /**
     * @param array<string, mixed> $request
     */
    public static function isNotification(array $request): bool
    {
        return !array_key_exists('id', $request);
    }
}
