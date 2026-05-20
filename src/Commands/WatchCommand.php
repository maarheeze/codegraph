<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Commands;

use Maarheeze\CodeGraph\Paths;
use Maarheeze\CodeGraph\Services\WatchService;
use Maarheeze\CodeGraph\Values\IndexStats;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function getcwd;
use function sprintf;

#[AsCommand('watch', 'Watch for file changes and automatically reindex')]
final class WatchCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cwd = getcwd();

        if ($cwd === false) {
            $output->writeln('<error>Error: Unable to determine current working directory</error>');

            return Command::FAILURE;
        }

        $output->writeln('<info>Watching for changes... (press Ctrl+C to stop)</info>');

        $service = new WatchService($cwd, Paths::databasePath($cwd));
        $service->run(
            static function (int $count) use ($output): void {
                $output->writeln(sprintf(
                    '<fg=yellow>%d file(s) changed, reindexing...</>',
                    $count,
                ));
            },
            static function (IndexStats $stats) use ($output): void {
                $output->writeln(sprintf(
                    '<fg=green>✓</> Indexed <fg=cyan>%d</> files, <fg=cyan>%d</> symbols, <fg=cyan>%d</> edges',
                    $stats->getFilesChanged(),
                    $stats->getSymbolsEmitted(),
                    $stats->getEdgesEmitted(),
                ));
            },
        );

        /** @phpstan-ignore-next-line deadCode.unreachable */
        return Command::SUCCESS;
    }
}
