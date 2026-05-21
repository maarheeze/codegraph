<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Mcp\Handlers;

use Maarheeze\CodeGraph\Contracts\Storage;
use Maarheeze\CodeGraph\Plugin\PluginRegistry;
use RuntimeException;
use Webmozart\Assert\Assert;

use function array_key_exists;
use function count;
use function sprintf;
use function substr;

final readonly class ToolHandler
{
    public function __construct(
        private Storage $storage,
        private PluginRegistry $pluginRegistry,
    ) {
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<string|int, mixed>
     */
    public function handle(string $name, array $arguments): array
    {
        $result = match ($name) {
            'codegraph_search' => $this->search($arguments),
            'codegraph_callers' => $this->callers($arguments),
            'codegraph_callees' => $this->callees($arguments),
            'codegraph_blast_radius' => $this->blastRadius($arguments),
            'codegraph_search_chunks' => $this->searchChunks($arguments),
            default => null,
        };

        if ($result !== null) {
            return $result;
        }

        foreach ($this->pluginRegistry->all() as $plugin) {
            $handlers = $plugin->getMcpToolHandlers();
            if (array_key_exists($name, $handlers)) {
                $handler = $handlers[$name];
                $result = $handler($arguments);
                Assert::isArray($result);
                return $result;
            }
        }

        throw new RuntimeException(sprintf('Unknown tool: %s', $name));
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<int, mixed>
     */
    private function search(array $arguments): array
    {
        $name = array_key_exists('name', $arguments) ? $arguments['name'] : null;
        Assert::string($name);

        $symbols = $this->storage->findByName($name);
        $result = [];

        foreach ($symbols as $symbol) {
            $result[] = [
                'kind' => $symbol->kind,
                'name' => $symbol->name,
                'fqn' => $symbol->fullyQualifiedName,
                'file' => $symbol->file,
                'line' => $symbol->startLine,
                'signature' => $symbol->signature,
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<int, mixed>
     */
    private function callers(array $arguments): array
    {
        $fqn = array_key_exists('fqn', $arguments) ? $arguments['fqn'] : null;
        Assert::string($fqn);

        $edges = $this->storage->findEdgesTo($fqn);
        $result = [];

        foreach ($edges as $edge) {
            $result[] = [
                'kind' => $edge->kind,
                'caller' => $edge->sourceFullyQualifiedName,
                'callee' => $edge->destinationFullyQualifiedName,
                'file' => $edge->file,
                'line' => $edge->line,
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<int, mixed>
     */
    private function callees(array $arguments): array
    {
        $fqn = array_key_exists('fqn', $arguments) ? $arguments['fqn'] : null;
        Assert::string($fqn);

        $edges = $this->storage->findEdgesFrom($fqn);
        $result = [];

        foreach ($edges as $edge) {
            $result[] = [
                'kind' => $edge->kind,
                'caller' => $edge->sourceFullyQualifiedName,
                'callee' => $edge->destinationFullyQualifiedName,
                'file' => $edge->file,
                'line' => $edge->line,
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, int|string|array<int, string>>
     */
    private function blastRadius(array $arguments): array
    {
        $fqn = array_key_exists('fqn', $arguments) ? $arguments['fqn'] : null;
        $depth = array_key_exists('depth', $arguments) ? $arguments['depth'] : 3;

        Assert::string($fqn);
        Assert::integer($depth);

        $affected = $this->storage->blastRadius($fqn, $depth);

        return [
            'query' => $fqn,
            'depth' => $depth,
            'affected_count' => count($affected),
            'affected_symbols' => $affected,
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<int, mixed>
     */
    private function searchChunks(array $arguments): array
    {
        $query = array_key_exists('query', $arguments) ? $arguments['query'] : null;
        Assert::string($query);

        $chunks = $this->storage->searchChunks($query);
        $result = [];

        foreach ($chunks as $chunk) {
            $result[] = [
                'fqn' => $chunk->fullyQualifiedName,
                'kind' => $chunk->kind,
                'file' => $chunk->file,
                'lines' => sprintf('%d-%d', $chunk->startLine, $chunk->endLine),
                'body' => substr($chunk->body, 0, 500),
            ];
        }

        return $result;
    }
}
