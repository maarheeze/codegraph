# CodeGraph

**Make AI agents faster and cheaper to use on your codebase.**

CodeGraph indexes your PHP code structure (classes, methods, functions, and relationships) and stores it locally. AI agents query this index instead of reading files.

> Inspired by [colbymchenry/codegraph](https://github.com/colbymchenry/codegraph) and [kha333n/laravel-code-graph](https://github.com/kha333n/laravel-code-graph)

**Result:**
- ⚡ **Faster** — Queries in milliseconds instead of reading files
- 💰 **Cheaper** — Use fewer tokens, no need to pass file contents  
- 🔍 **Accurate** — Structured data about code relationships, not guesses

Ask your AI agent questions directly:
- "Who calls this method?"
- "What breaks if I change this class?"
- "Show me all usages of this function"
- "What's the impact radius of this change?"

## How It Works

1. **Index your code** — `php vendor/bin/codegraph index` (one-time)
2. **AI agent uses it** — Register CodeGraph as an MCP server (works with Claude Code and any MCP-compatible AI)
3. **Faster context** — AI queries the index instead of reading files

Works with [Claude Code](https://claude.com/claude-code), [Cursor](https://cursor.sh), and any AI supporting MCP protocol.

## Installation

```bash
composer require maarheeze/codegraph --dev
php vendor/bin/codegraph init
php vendor/bin/codegraph index
```

That's it. CodeGraph will:
1. Create a `.codegraph/` directory with SQLite database (should be ignored by git!)
2. Scan your code (`app/` and `src/` directories by default)
3. Extract symbols, relationships, and code structure
4. Build the index

**Optional: Auto-reindex on changes**

For continuous development, keep CodeGraph in sync automatically:

```bash
php vendor/bin/codegraph watch
```


This watches your code for changes and reindexes automatically. Press `Ctrl+C` to stop. To automatically initialize and index your codebase after dependencies are installed or updated, add the following to your `composer.json`:

```json
{
  "scripts": {
    "post-autoload-dump": [
      "@php artisan codegraph:init || true",
      "@php artisan codegraph:index || true"
    ]
  }
}
```

Verify it worked:

```bash
php vendor/bin/codegraph status
```

This shows how many symbols, edges, and files were indexed. 

## Usage

### CLI Search

```bash
php vendor/bin/codegraph search User
```

### Claude Code Integration

CodeGraph works with Claude Code via the MCP protocol.

**Automatic Setup (Recommended)**

Running `php vendor/bin/codegraph init` automatically:
1. Creates `.codegraph/` directory and SQLite database
2. Detects your environment (Sail, Docker, or plain PHP)
3. Configures `.mcp.json` with the correct MCP command
4. Adds CodeGraph guidelines to your `CLAUDE.md`

That's it! Claude Code will automatically discover and use CodeGraph's tools.

**Manual Setup (if needed)**

If you need to manually configure `.mcp.json`:

```json
{
  "mcpServers": {
    "codegraph": {
      "command": "vendor/bin/sail",
      "args": ["php", "vendor/bin/codegraph", "mcp"]
    }
  }
}
```

Replace `vendor/bin/sail` with:
- `php` for plain PHP projects
- `docker` with args `["compose", "exec", "-it", "app", "php", "vendor/bin/codegraph", "mcp"]` for Docker Compose (replace `app` with your actual service name)

**Then ask Claude Code:**

- "Search for the User model"
- "Who calls the create method on User?"
- "What breaks if I change this service?"
- "Find all usages of the payment provider"

**Available tools:**

- `codegraph_search` — Find symbols by name or FQN
- `codegraph_callers` — Who calls a symbol
- `codegraph_callees` — What a symbol calls
- `codegraph_blast_radius` — Impact of changing a symbol
- `codegraph_search_chunks` — Full-text search across code

## Commands

**`init`** — Initialize CodeGraph in your project
```bash
php vendor/bin/codegraph init
```

Optional: Explicitly set MCP configuration (auto-detected by default):
```bash
# Use Sail
php vendor/bin/codegraph init sail

# Use Docker Compose
php vendor/bin/codegraph init docker

# Use plain PHP (no Docker)
php vendor/bin/codegraph init php
```

If not specified, CodeGraph auto-detects your environment:
- Checks for `vendor/bin/sail` → uses Sail
- Checks for `docker-compose.yml` → uses Docker
- Falls back to plain PHP otherwise

**`index`** — Index your code (tracks changes, only re-parses what changed)
```bash
php vendor/bin/codegraph index
```

**`watch`** — Auto-reindex when files change (useful during development)
```bash
php vendor/bin/codegraph watch
```

**`status`** — See indexing statistics
```bash
php vendor/bin/codegraph status
```

**`mcp`** — Start MCP server (for Claude Code integration)
```bash
php vendor/bin/codegraph mcp
```

**`search`** — CLI symbol search
```bash
php vendor/bin/codegraph search User
```

## Configuration

### Customize scan paths

Default: `src/` and `app/`

```bash
php vendor/bin/codegraph index --path app --path lib
```

### Customize excludes

Default: `vendor/`, `node_modules/`, `storage/`

```bash
php vendor/bin/codegraph index --path app --exclude tests --exclude migrations
```

### Re-index your code

CodeGraph tracks file hashes and only re-parses changed files:

```bash
php vendor/bin/codegraph index
```

To force full re-index:

```bash
rm -rf .codegraph
php vendor/bin/codegraph init
php vendor/bin/codegraph index
```

## What Gets Indexed

**Symbols:** Classes, interfaces, traits, enums, methods, functions, properties

**Relationships:** Method calls, function calls, constructors, inheritance (`extends`/`implements`/`uses`), parent calls

**Metadata:** File paths, line numbers, visibility (public/protected/private), static/abstract markers, method signatures

## Known Limitations

- Dynamic class names (`new $className()`) are not resolved
- Variable method calls (`$obj->$method()`) are not detected  
- Closures and callbacks are not indexed
- External/vendor code (Composer packages) is not indexed
- Template files (Blade, Twig, etc.) are not analyzed
- Service container resolution is not automatic

## Roadmap

- [ ] **100% code coverage** — Comprehensive unit and integration tests
- [ ] **Index template files** — Support for Blade, Twig or HTML templates (core package or plugin TBD)
- [ ] **Extensive documentation** — Architecture guides, API reference, plugin development docs

## Incremental Sync

CodeGraph uses file hashing to detect changes:

```bash
php vendor/bin/codegraph index
```

Only files that changed since last index are re-parsed. This makes indexing fast on large codebases.

To see what changed:

```bash
php vendor/bin/codegraph status
```

## API Usage (PHP)

```php
use Maarheeze\CodeGraph\CodeGraph;

// Initialize for current project
$codeGraph = CodeGraph::forProject();

// Get statistics
$stats = $codeGraph->stats();
echo "Symbols: " . $stats['symbols'];

// Index code
$indexStats = $codeGraph->index();
echo "Files indexed: " . $indexStats->getFilesChanged();

// Query the storage directly
$storage = $codeGraph->getStorage();
$symbols = $storage->findByName('User');
$callers = $storage->findEdgesTo('\App\Models\User::create');
$callees = $storage->findEdgesFrom('\App\Models\User::create');
$affected = $storage->blastRadius('\App\Models\User::create', depth: 3);
```

## Database Schema

CodeGraph uses SQLite with the following tables:

- `symbols` — All indexed symbols (classes, methods, functions, etc.)
- `edges` — Relationships between symbols (calls, inheritance, etc.)
- `chunks` — Code snippets indexed for full-text search
- `files` — File metadata (paths, hashes, timestamps)

Direct database queries are supported for custom analysis.

## License

MIT
