<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

#[AsCommand('blast-radius', 'Calculate the full impact radius of changing a symbol (JSON output)')]
final class BlastRadiusCommand extends AbstractQueryCommand
{
    protected function configure(): void
    {
        $this->addArgument('fqn', InputArgument::REQUIRED, 'Fully qualified name of the symbol to analyze');
        $this->addOption('depth', null, InputOption::VALUE_REQUIRED, 'Maximum depth to traverse', '3');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $service = $this->queryService($output);

        if ($service === null) {
            return Command::FAILURE;
        }

        $fqn = $input->getArgument('fqn');
        Assert::string($fqn);

        $depth = $input->getOption('depth');
        Assert::numeric($depth);

        return $this->writeJson($output, $service->blastRadius($fqn, (int) $depth));
    }
}
