<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Commands;

use Maarheeze\CodeGraph\Paths;
use Maarheeze\CodeGraph\Services\StatusService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function getcwd;
use function sprintf;

#[AsCommand('status', 'Show index statistics')]
final class StatusCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cwd = getcwd();

        if ($cwd === false) {
            $output->writeln('<error>Error: Unable to determine current working directory</error>');

            return Command::FAILURE;
        }

        $service = new StatusService(Paths::databasePath($cwd));
        $result = $service->run();

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
