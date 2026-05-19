<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Commands;

use Maarheeze\CodeGraph\CodeGraph;
use Maarheeze\CodeGraph\Mcp\McpServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function fwrite;
use function sprintf;

use const STDERR;

#[AsCommand('mcp', 'Start the MCP server')]
final class McpCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $codeGraph = CodeGraph::forProject();
            $server = new McpServer($codeGraph->getStorage());
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
