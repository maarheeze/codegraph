<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Commands;

use Maarheeze\CodeGraph\CodeGraph;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

use function array_key_exists;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function getcwd;
use function is_array;
use function is_dir;
use function json_decode;
use function json_encode;
use function mkdir;
use function sprintf;
use function str_contains;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;

#[AsCommand('init', 'Initialize CodeGraph database')]
final class InitCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument(
            'mcp-config',
            InputArgument::OPTIONAL,
            'MCP server configuration: auto (default), sail, docker, or php',
            'auto',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cwd = getcwd();

        if ($cwd === false) {
            $output->writeln('<error>Error: Unable to determine current working directory</error>');

            return Command::FAILURE;
        }

        $cgDir = sprintf('%s/.codegraph', $cwd);

        if (!is_dir($cgDir)) {
            mkdir($cgDir, 0755, true);
        }

        CodeGraph::forProject($cwd);

        $output->writeln(sprintf('<info>Initialized CodeGraph database at: %s</info>', $cgDir));

        $claudeMdPath = sprintf('%s/CLAUDE.md', $cwd);
        $claudeContent = '';
        if (file_exists($claudeMdPath)) {
            $content = file_get_contents($claudeMdPath);
            $claudeContent = $content !== false ? $content : '';
        }

        if (!str_contains($claudeContent, '<!-- codegraph -->')) {
            $section = <<<'MD'
            <!-- codegraph -->
            ## CodeGraph

            This project has CodeGraph installed with a pre-built index of all PHP symbols
            and their call graph relationships.

            **CRITICAL:** For any question about code structure, relationships, or impact —
            ALWAYS use the CodeGraph MCP tools. Do NOT use bash, grep, file search, or IDE
            symbol search. The graph is faster, cheaper, and more accurate than text search.

            ### Decision Rules

            - "Where is X defined?" → `codegraph_search`
            - "Where is X used?" / "Who calls X?" → `codegraph_callers`
            - "What does X call?" / "What does X depend on?" → `codegraph_callees`
            - "What breaks if I change X?" → `codegraph_blast_radius`
            - "Where is string/pattern Y used?" → `codegraph_search_chunks`

            ### Workflow

            1. `codegraph_search(name)` → get exact FQN (e.g. `\App\Models\User`)
            2. Use the appropriate relationship tool with that FQN
            3. Only read files for implementation details not available in the index
            <!-- /codegraph -->
            MD;

            $newContent = $claudeContent
                ? sprintf("%s\n\n%s", $claudeContent, $section)
                : sprintf('%s%s', $section, PHP_EOL);
            file_put_contents($claudeMdPath, $newContent);
            $output->writeln('<info>Added CodeGraph guidelines to CLAUDE.md</info>');
        }

        $mcpConfig = $input->getArgument('mcp-config');
        Assert::string($mcpConfig);
        $this->registerMcpServer($cwd, $output, $mcpConfig);

        return Command::SUCCESS;
    }

    private function registerMcpServer(string $cwd, OutputInterface $output, string $mcpConfig = 'auto'): void
    {
        $mcpJsonPath = sprintf('%s/.mcp.json', $cwd);

        $config = [];
        if (file_exists($mcpJsonPath)) {
            $content = file_get_contents($mcpJsonPath);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $config = $decoded;
                }
            }
        }

        if (!array_key_exists('mcpServers', $config)) {
            $config['mcpServers'] = [];
        }

        Assert::isArray($config);
        $mcpServers = $config['mcpServers'];
        if (!is_array($mcpServers)) {
            return;
        }

        if (array_key_exists('codegraph', $mcpServers)) {
            return;
        }

        $mcpServers['codegraph'] = $this->detectMcpCommand($cwd, $mcpConfig);
        $config['mcpServers'] = $mcpServers;

        file_put_contents($mcpJsonPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        $output->writeln('<info>Registered CodeGraph MCP server in .mcp.json</info>');
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    private function detectMcpCommand(string $cwd, string $mcpConfig = 'auto'): array
    {
        if ($mcpConfig === 'sail') {
            return [
                'command' => 'vendor/bin/sail',
                'args' => ['php', 'vendor/bin/codegraph', 'mcp'],
            ];
        }

        if ($mcpConfig === 'docker') {
            return [
                'command' => 'docker',
                'args' => ['compose', 'exec', '-it', 'laravel.test', 'php', 'vendor/bin/codegraph', 'mcp'],
            ];
        }

        if ($mcpConfig === 'php') {
            return [
                'command' => 'php',
                'args' => ['vendor/bin/codegraph', 'mcp'],
            ];
        }

        $sailPath = sprintf('%s/vendor/bin/sail', $cwd);
        $dockerComposePath = sprintf('%s/docker-compose.yml', $cwd);

        if (file_exists($sailPath)) {
            return [
                'command' => 'vendor/bin/sail',
                'args' => ['php', 'vendor/bin/codegraph', 'mcp'],
            ];
        }

        if (file_exists($dockerComposePath)) {
            return [
                'command' => 'docker',
                'args' => ['compose', 'exec', '-it', 'laravel.test', 'php', 'vendor/bin/codegraph', 'mcp'],
            ];
        }

        return [
            'command' => 'php',
            'args' => ['vendor/bin/codegraph', 'mcp'],
        ];
    }
}
