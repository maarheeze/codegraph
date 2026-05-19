<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Commands;

use Maarheeze\CodeGraph\CodeGraph;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_values;
use function count;
use function getcwd;
use function is_dir;
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
        $scanPaths = array_values($scanPaths);

        $scanPaths = array_filter($scanPaths, static function (string $path) use ($cwd): bool {
            return is_dir(sprintf('%s/%s', $cwd, $path));
        });
        $scanPaths = array_values($scanPaths);

        if ($scanPaths === []) {
            $output->writeln('<error>Error: No scan directories found. Create src/ or app/ or specify --path</error>');

            return Command::FAILURE;
        }

        $output->writeln('<comment>Scanning directories:</comment>');
        foreach ($scanPaths as $path) {
            $output->writeln(sprintf('  <comment>-</comment> %s', $path));
        }

        Assert::isArray($excludes);
        Assert::allString($excludes);
        $excludes = array_values($excludes);

        $codeGraph = new CodeGraph(
            $cwd,
            sprintf('%s/.codegraph/index.sqlite', $cwd),
            $scanPaths,
            $excludes,
        );

        $output->writeln('<info>Starting index...</info>');
        $stats = $codeGraph->index();

        $output->writeln('<info>Index complete:</info>');
        $output->writeln(sprintf('  Files scanned:   <fg=cyan>%d</>', $stats->getFilesScanned()));
        $output->writeln(sprintf('  Files changed:   <fg=cyan>%d</>', $stats->getFilesChanged()));
        $output->writeln(sprintf('  Files skipped:   <fg=cyan>%d</>', $stats->getFilesSkipped()));
        $output->writeln(sprintf('  Files failed:    <fg=cyan>%d</>', $stats->getFilesFailed()));
        $output->writeln(sprintf('  Symbols emitted: <fg=cyan>%d</>', $stats->getSymbolsEmitted()));
        $output->writeln(sprintf('  Edges emitted:   <fg=cyan>%d</>', $stats->getEdgesEmitted()));
        $output->writeln(sprintf('  Chunks emitted:  <fg=cyan>%d</>', $stats->getChunksEmitted()));
        $output->writeln(sprintf('  Duration:        <fg=cyan>%.2fs</>', $stats->getDurationSeconds()));

        if (count($stats->getErrors()) > 0) {
            $output->writeln('<error>Errors encountered:</error>');
            foreach ($stats->getErrors() as $error) {
                $output->writeln(sprintf('  <error>-</error> %s', $error));
            }

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
