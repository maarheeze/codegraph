<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Mcp\Handlers;

use Maarheeze\CodeGraph\Contracts\Storage;
use Maarheeze\CodeGraph\Plugin\PluginRegistry;
use Maarheeze\CodeGraph\Services\QueryService;
use RuntimeException;
use Webmozart\Assert\Assert;

use function array_key_exists;
use function sprintf;

final readonly class ToolHandler
{
    private QueryService $queryService;

    public function __construct(
        private Storage $storage,
        private PluginRegistry $pluginRegistry,
    ) {
        $this->queryService = new QueryService($storage);
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<string|int, mixed>
     */
    public function handle(string $name, array $arguments): array
    {
        $result = match ($name) {
            'codegraph_search' => $this->queryService->search($this->stringArgument($arguments, 'name')),
            'codegraph_callers' => $this->queryService->callers($this->stringArgument($arguments, 'fqn')),
            'codegraph_callees' => $this->queryService->callees($this->stringArgument($arguments, 'fqn')),
            'codegraph_blast_radius' => $this->queryService->blastRadius(
                $this->stringArgument($arguments, 'fqn'),
                $this->depthArgument($arguments),
            ),
            'codegraph_search_chunks' => $this->queryService->searchChunks($this->stringArgument($arguments, 'query')),
            default => null,
        };

        if ($result !== null) {
            return $result;
        }

        foreach ($this->pluginRegistry->all() as $plugin) {
            $handlers = $plugin->getMcpToolHandlers($this->storage);
            if (array_key_exists($name, $handlers)) {
                $handler = $handlers[$name];

                return $handler($arguments);
            }
        }

        throw new RuntimeException(sprintf('Unknown tool: %s', $name));
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function depthArgument(array $arguments): int
    {
        $depth = array_key_exists('depth', $arguments) ? $arguments['depth'] : 3;
        Assert::integer($depth);

        return $depth;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function stringArgument(array $arguments, string $key): string
    {
        $value = array_key_exists($key, $arguments) ? $arguments[$key] : null;
        Assert::string($value);

        return $value;
    }
}
