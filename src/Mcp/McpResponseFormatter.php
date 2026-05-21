<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Mcp;

use stdClass;

use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

final readonly class McpResponseFormatter
{
    /**
     * @return array<string, mixed>
     */
    public static function error(mixed $id, string $jsonrpc, int $code, string $message): array
    {
        return [
            'jsonrpc' => $jsonrpc,
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function initialize(mixed $id, string $jsonrpc): array
    {
        return [
            'jsonrpc' => $jsonrpc,
            'id' => $id,
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => new stdClass(),
                ],
                'serverInfo' => [
                    'name' => 'codegraph',
                    'version' => '1.0.0',
                ],
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $tools
     * @return array<string, mixed>
     */
    public static function toolsList(mixed $id, string $jsonrpc, array $tools): array
    {
        return [
            'jsonrpc' => $jsonrpc,
            'id' => $id,
            'result' => [
                'tools' => $tools,
            ],
        ];
    }

    /**
     * @param array<string|int, mixed> $result
     * @return array<string, mixed>
     */
    public static function toolCall(mixed $id, string $jsonrpc, array $result): array
    {
        return [
            'jsonrpc' => $jsonrpc,
            'id' => $id,
            'result' => [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                    ],
                ],
            ],
        ];
    }
}
