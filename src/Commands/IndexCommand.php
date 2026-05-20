<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Commands;

use Maarheeze\CodeGraph\Paths;
use Maarheeze\CodeGraph\Services\IndexingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

use function array_values;
use function getcwd;
use function sprintf;

#[AsCommand('index', 'Index PHP files')]
final class IndexCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'path',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Scan paths (can be specified multiple times)',
            ['src', 'app'],
        );

        $this->addOption(
            'exclude',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Exclude patterns (can be specified multiple times)',
            ['vendor', 'node_modules', 'storage'],
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cwd = getcwd();

        if ($cwd === false) {
            $output->writeln('<error>Error: Unable to determine current working directory</error>');

            return Command::FAILURE;
        }

        $scanPaths = $input->getOption('path');
        $excludes = $input->getOption('exclude');

        Assert::isArray($scanPaths);
        Assert::allString($scanPaths);
        Assert::isArray($excludes);
        Assert::allString($excludes);

        $service = new IndexingService();
        $result = $service->run(
            $cwd,
            Paths::databasePath($cwd),
            array_values($scanPaths),
            array_values($excludes),
        );

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
