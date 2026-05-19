<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Mcp;

use Maarheeze\CodeGraph\Contracts\Storage;
use Maarheeze\CodeGraph\Mcp\Handlers\ToolHandler;
use RuntimeException;
use Throwable;
use Webmozart\Assert\Assert;

use function json_encode;
use function php_sapi_name;
use function sprintf;

final readonly class McpServer
{
    private ToolHandler $toolHandler;

    public function __construct(
        Storage $storage,
    ) {
        $this->toolHandler = new ToolHandler($storage);
    }

    public function start(): void
    {
        if (php_sapi_name() !== 'cli') {
            throw new RuntimeException('MCP server must run in CLI mode');
        }

        $streams = new McpStreams();
        $streams->logToStderr('CodeGraph MCP server ready');

        try {
            while (true) {
                $line = $streams->readLine();

                if ($line === null) {
                    break;
                }

                $request = McpRequestParser::parse($line);

                if ($request === null) {
                    $streams->logToStderr(sprintf('Failed to decode JSON: %s', $line));
                    continue;
                }

                if (McpRequestParser::isNotification($request)) {
                    continue;
                }

                $this->processRequest($streams, $request);
            }
        } finally {
            $streams->close();
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    private function processRequest(McpStreams $streams, array $request): void
    {
        try {
            $extracted = McpRequestExtractor::extract($request);
            $response = $this->handleRequest(
                $extracted['method'],
                $extracted['params'],
                $extracted['id'],
                $extracted['jsonrpc'],
            );
            $encoded = json_encode($response);
            Assert::string($encoded);
            $streams->writeLine($encoded);
        } catch (Throwable $e) {
            $streams->logToStderr(sprintf('Error handling request: %s', $e->getMessage()));
            $jsonrpc = $request['jsonrpc'] ?? '2.0';
            Assert::string($jsonrpc);
            $response = McpResponseFormatter::error(
                $request['id'] ?? null,
                $jsonrpc,
                -32603,
                $e->getMessage(),
            );
            $encoded = json_encode($response);
            Assert::string($encoded);
            $streams->writeLine($encoded);
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function handleRequest(string $method, array $params, mixed $id, string $jsonrpc): array
    {
        return match ($method) {
            'initialize' => McpResponseFormatter::initialize($id, $jsonrpc),
            'tools/list' => McpResponseFormatter::toolsList($id, $jsonrpc),
            'tools/call' => $this->handleToolCall($id, $jsonrpc, $params),
            default => McpResponseFormatter::error($id, $jsonrpc, -32601, 'Method not found'),
        };
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function handleToolCall(mixed $id, string $jsonrpc, array $params): array
    {
        $name = $params['name'] ?? null;
        Assert::string($name);

        $arguments = $params['arguments'] ?? [];
        Assert::isArray($arguments);
        /** @var array<string, mixed> $arguments */

        $result = $this->toolHandler->handle($name, $arguments);

        return McpResponseFormatter::toolCall($id, $jsonrpc, $result);
    }
}
