<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Commands;

use Maarheeze\CodeGraph\CodeGraph;
use Maarheeze\CodeGraph\Services\QueryService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

use function getcwd;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

abstract class AbstractQueryCommand extends Command
{
    protected function queryService(OutputInterface $output): ?QueryService
    {
        $cwd = getcwd();

        if ($cwd === false) {
            $output->writeln('<error>Error: Unable to determine current working directory</error>');

            return null;
        }

        return new QueryService(CodeGraph::forProject($cwd)->getStorage());
    }

    protected function writeJson(OutputInterface $output, mixed $data): int
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        Assert::string($json);
        $output->writeln($json);

        return Command::SUCCESS;
    }
}
