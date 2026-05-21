<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Commands;

use Maarheeze\CodeGraph\CodeGraph;
use Maarheeze\CodeGraph\Mcp\McpServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Webmozart\Assert\Assert;

use function fwrite;
use function sprintf;

use const STDERR;

#[AsCommand('mcp', 'Start the MCP server')]
final class McpCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'root',
            null,
            InputOption::VALUE_REQUIRED,
            'Project root directory',
            '.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $root = $input->getOption('root');
            Assert::string($root);
            $codeGraph = CodeGraph::forProject($root);
            $server = new McpServer($codeGraph->getStorage(), $codeGraph->getPluginRegistry());
            $server->start();
        } catch (Throwable $e) {
            fwrite(STDERR, sprintf(
                "MCP Server Error: %s\n\n"
                . "Make sure to run 'codegraph init' and 'codegraph index' first.\n"
                . "Full error: %s\n",
                $e->getMessage(),
                $e,
            ));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
