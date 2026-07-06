<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

#[AsCommand('search', 'Search for symbols by name or FQN (JSON output)')]
final class SearchCommand extends AbstractQueryCommand
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Symbol name or partial FQN to search for');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $service = $this->queryService($output);

        if ($service === null) {
            return Command::FAILURE;
        }

        $name = $input->getArgument('name');
        Assert::string($name);

        return $this->writeJson($output, $service->search($name));
    }
}
