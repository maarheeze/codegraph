<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

#[AsCommand('callers', 'Find all callers of a symbol — who uses/calls X (JSON output)')]
final class CallersCommand extends AbstractQueryCommand
{
    protected function configure(): void
    {
        $this->addArgument('fqn', InputArgument::REQUIRED, 'Fully qualified name of the symbol');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $service = $this->queryService($output);

        if ($service === null) {
            return Command::FAILURE;
        }

        $fqn = $input->getArgument('fqn');
        Assert::string($fqn);

        return $this->writeJson($output, $service->callers($fqn));
    }
}
