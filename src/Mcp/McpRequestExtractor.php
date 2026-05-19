<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Mcp;

use Webmozart\Assert\Assert;

use function array_key_exists;
use function is_array;
use function is_string;

final readonly class McpRequestExtractor
{
    /**
     * @param array<string, mixed> $request
     * @return array{jsonrpc: string, id: mixed, method: string, params: array<string, mixed>}
     */
    public static function extract(array $request): array
    {
        $jsonrpc = (array_key_exists('jsonrpc', $request) && is_string($request['jsonrpc']))
            ? $request['jsonrpc']
            : '2.0';
        $id = array_key_exists('id', $request) ? $request['id'] : null;
        $method = array_key_exists('method', $request) ? $request['method'] : null;
        $paramsRaw = (array_key_exists('params', $request) && is_array($request['params']))
            ? $request['params']
            : [];

        $params = [];
        foreach ($paramsRaw as $key => $value) {
            Assert::string($key);
            $params[$key] = $value;
        }

        Assert::string($method);
        Assert::string($jsonrpc);

        return [
            'jsonrpc' => $jsonrpc,
            'id' => $id,
            'method' => $method,
            'params' => $params,
        ];
    }
}
