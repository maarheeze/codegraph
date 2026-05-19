<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Mcp;

final readonly class McpToolRegistry
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function tools(): array
    {
        return [
            [
                'name' => 'codegraph_search',
                'description' => 'Search for symbols by name or FQN',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'Symbol name or partial FQN to search for',
                        ],
                    ],
                    'required' => ['name'],
                ],
                'outputSchema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'kind' => [
                                'type' => 'string',
                                'description' => 'Symbol kind (class, function, method, etc.)',
                            ],
                            'name' => ['type' => 'string', 'description' => 'Symbol name'],
                            'fqn' => ['type' => 'string', 'description' => 'Fully qualified name'],
                            'file' => ['type' => 'string', 'description' => 'File path'],
                            'line' => ['type' => 'integer', 'description' => 'Start line number'],
                            'signature' => [
                                'type' => 'string',
                                'description' => 'Method/function signature',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'codegraph_callers',
                'description' => 'Find all callers of a symbol (who uses/calls X).'
                    . ' Use for "where is X used", "who calls X", "what depends on X".'
                    . ' Prefer over IDE symbol search or grep.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'fqn' => [
                            'type' => 'string',
                            'description' => 'Fully qualified name of the symbol',
                        ],
                    ],
                    'required' => ['fqn'],
                ],
                'outputSchema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'kind' => ['type' => 'string', 'description' => 'Edge kind (call, instantiation, etc.)'],
                            'caller' => ['type' => 'string', 'description' => 'FQN of the caller'],
                            'callee' => ['type' => 'string', 'description' => 'FQN of the callee'],
                            'file' => ['type' => 'string', 'description' => 'File where the call occurs'],
                            'line' => ['type' => 'integer', 'description' => 'Line number of the call'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'codegraph_callees',
                'description' => 'Find all symbols a function/method calls or depends on.'
                    . ' Use for "what does X call", "what does X depend on".',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'fqn' => [
                            'type' => 'string',
                            'description' => 'Fully qualified name of the symbol',
                        ],
                    ],
                    'required' => ['fqn'],
                ],
                'outputSchema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'kind' => ['type' => 'string', 'description' => 'Edge kind (call, instantiation, etc.)'],
                            'caller' => ['type' => 'string', 'description' => 'FQN of the caller'],
                            'callee' => ['type' => 'string', 'description' => 'FQN of the callee'],
                            'file' => ['type' => 'string', 'description' => 'File where the call occurs'],
                            'line' => ['type' => 'integer', 'description' => 'Line number of the call'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'codegraph_blast_radius',
                'description' => 'Calculate the full impact radius of changing a symbol.'
                    . ' Use for "what breaks if I change X", refactoring scope assessment.'
                    . ' Always use before renaming or deleting code.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'fqn' => [
                            'type' => 'string',
                            'description' => 'Fully qualified name of the symbol to analyze',
                        ],
                        'depth' => [
                            'type' => 'integer',
                            'description' => 'Maximum depth to traverse (default: 3)',
                            'default' => 3,
                        ],
                    ],
                    'required' => ['fqn'],
                ],
                'outputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'The queried FQN'],
                        'depth' => ['type' => 'integer', 'description' => 'Traversal depth used'],
                        'affected_count' => ['type' => 'integer', 'description' => 'Total count of affected symbols'],
                        'affected_symbols' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'List of affected symbol FQNs',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'codegraph_search_chunks',
                'description' => 'Full-text search across symbol bodies',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'Search query (FTS5 syntax)',
                        ],
                    ],
                    'required' => ['query'],
                ],
                'outputSchema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'fqn' => [
                                'type' => 'string',
                                'description' => 'Fully qualified name of the symbol',
                            ],
                            'kind' => [
                                'type' => 'string',
                                'description' => 'Symbol kind (class, function, method, etc.)',
                            ],
                            'file' => ['type' => 'string', 'description' => 'File path'],
                            'lines' => [
                                'type' => 'string',
                                'description' => 'Line range (e.g., "10-25")',
                            ],
                            'body' => [
                                'type' => 'string',
                                'description' => 'Symbol body (first 500 characters)',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
