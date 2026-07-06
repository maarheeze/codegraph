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
2. **AI agent uses it** — either via [CLI query commands](#cli-queries-token-efficient-no-mcp) (cheapest, no always-on token cost) or by registering CodeGraph as an MCP server (works with Claude Code and any MCP-compatible AI)
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
5. Add token-efficient CLI guidelines to your `CLAUDE.md` (no MCP server registered — see [CLI Queries](#cli-queries-token-efficient-no-mcp))

Prefer the always-on MCP server instead? Run `php vendor/bin/codegraph init --mcp` (see [Claude Code Integration](#claude-code-integration)).

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

### CLI Queries (token-efficient, no MCP)

Every MCP tool is also available as a CLI command that prints JSON to stdout. This is the **cheapest way to give an AI agent access**: an MCP server injects all of its tool definitions into the agent's context on *every* session (whether used or not), while a CLI is discovered once and only costs tokens when the agent actually runs it.

```bash
php vendor/bin/codegraph search User
php vendor/bin/codegraph callers '\App\Models\User::create'
php vendor/bin/codegraph callees '\App\Models\User::create'
php vendor/bin/codegraph blast-radius '\App\Models\User::create' --depth=3
php vendor/bin/codegraph search-chunks 'payment provider'
```

Each command returns the same structured JSON the MCP tools return, so an agent can parse the output directly. This is the **default**: `php vendor/bin/codegraph init` adds a hint to your `CLAUDE.md` pointing Claude Code at `php vendor/bin/codegraph <search|callers|callees|blast-radius|search-chunks>` — no `.mcp.json` entry, no always-on token cost.

#### Migrating from MCP to the CLI

If you previously ran `init` with MCP enabled, `init` will **not** overwrite the existing setup — it skips `CLAUDE.md` when it finds the `<!-- codegraph -->` marker and leaves your MCP config untouched. To switch a project fully to the CLI:

1. Remove the `<!-- codegraph -->` … `<!-- /codegraph -->` block from `CLAUDE.md`
2. Delete the `codegraph` entry from `.mcp.json`
3. Remove `codegraph` from `enabledMcpjsonServers` in `.claude/settings.local.json`
4. Re-run `php vendor/bin/codegraph init`

### Claude Code Integration

CLI queries (above) are the default and the cheapest option. CodeGraph can also run as an MCP server if you prefer that workflow — but note that an MCP server injects all of its tool definitions into the agent's context on *every* session, whether used or not.

**Automatic Setup**

Running `php vendor/bin/codegraph init --mcp` automatically:
1. Creates `.codegraph/` directory and SQLite database
2. Detects your environment (Sail, Docker, or plain PHP)
3. Configures `.mcp.json` with the correct MCP command
4. Adds CodeGraph MCP guidelines to your `CLAUDE.md`

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

**`init`** — Initialize CodeGraph in your project (CLI query mode, no MCP)
```bash
php vendor/bin/codegraph init
```

Opt into the MCP server instead with `--mcp`:
```bash
php vendor/bin/codegraph init --mcp
```

Optional: with `--mcp`, explicitly set the MCP command (auto-detected by default):
```bash
# Use Sail
php vendor/bin/codegraph init --mcp sail

# Use Docker Compose
php vendor/bin/codegraph init --mcp docker

# Use plain PHP (no Docker)
php vendor/bin/codegraph init --mcp php
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

**Query commands** — token-efficient CLI alternative to the MCP tools, all printing JSON to stdout:

```bash
# Find symbols by name or FQN
php vendor/bin/codegraph search User

# Who calls a symbol
php vendor/bin/codegraph callers '\App\Models\User::create'

# What a symbol calls / depends on
php vendor/bin/codegraph callees '\App\Models\User::create'

# Impact of changing a symbol (optional --depth, default 3)
php vendor/bin/codegraph blast-radius '\App\Models\User::create' --depth=3

# Full-text search across code bodies (FTS5 syntax)
php vendor/bin/codegraph search-chunks 'payment provider'
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
