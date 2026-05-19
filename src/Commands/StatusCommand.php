<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Commands;

use Maarheeze\CodeGraph\CodeGraph;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function file_exists;
use function filesize;
use function floor;
use function getcwd;
use function log;
use function max;
use function min;
use function round;
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

        $codeGraph = CodeGraph::forProject($cwd);
        $stats = $codeGraph->stats();
        $dbPath = sprintf('%s/.codegraph/index.sqlite', $cwd);
        $dbSize = 0;

        if (file_exists($dbPath)) {
            $dbSize = filesize($dbPath);
        }


        $output->writeln('<info>CodeGraph Status:</info>');
        $output->writeln(sprintf('  Symbols: <fg=cyan>%d</>', $stats['symbols']));
        $output->writeln(sprintf('  Edges:   <fg=cyan>%d</>', $stats['edges']));
        $output->writeln(sprintf('  Chunks:  <fg=cyan>%d</>', $stats['chunks']));
        $output->writeln(sprintf('  Files:   <fg=cyan>%d</>', $stats['files']));
        $output->writeln(sprintf('  DB path: <fg=cyan>%s</>', $dbPath));
        $output->writeln(sprintf('  DB size: <fg=cyan>%s</>', $dbSize > 0 ? $this->formatBytes($dbSize) : 'N/A'));

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = (int) min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return sprintf('%s %s', round($bytes, 2), $units[$pow]);
    }
}
