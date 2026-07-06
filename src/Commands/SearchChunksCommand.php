<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

#[AsCommand('search-chunks', 'Full-text search across symbol bodies using FTS5 syntax (JSON output)')]
final class SearchChunksCommand extends AbstractQueryCommand
{
    protected function configure(): void
    {
        $this->addArgument('query', InputArgument::REQUIRED, 'Search query (FTS5 syntax)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $service = $this->queryService($output);

        if ($service === null) {
            return Command::FAILURE;
        }

        $query = $input->getArgument('query');
        Assert::string($query);

        return $this->writeJson($output, $service->searchChunks($query));
    }
}
