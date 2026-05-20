<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Commands;

use Maarheeze\CodeGraph\Services\InitializationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

use function getcwd;
use function sprintf;

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

        $mcpConfig = $input->getArgument('mcp-config');
        Assert::string($mcpConfig);

        $service = new InitializationService();
        $result = $service->run($cwd, $mcpConfig);

        if ($result['error'] !== null) {
            $output->writeln(sprintf('<error>Error: %s</error>', $result['error']));

            return Command::FAILURE;
        }

        foreach ($result['lines'] as $line) {
            $output->writeln($line);
        }

        return Command::SUCCESS;
    }
}
