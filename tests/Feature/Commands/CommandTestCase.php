<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Tests\Feature\Commands;

use Maarheeze\CodeGraph\Paths;
use Maarheeze\CodeGraph\Storage\Sqlite\SqliteGraph;
use Maarheeze\CodeGraph\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Webmozart\Assert\Assert;

use function chdir;
use function getcwd;
use function is_dir;
use function json_decode;
use function mkdir;
use function rmdir;
use function scandir;
use function sprintf;
use function sys_get_temp_dir;
use function trim;
use function uniqid;
use function unlink;

abstract class CommandTestCase extends TestCase
{
    protected string $projectRoot;

    private string $originalWorkingDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $cwd = getcwd();
        Assert::string($cwd);
        $this->originalWorkingDirectory = $cwd;

        $this->projectRoot = sprintf('%s/codegraph-test-%s', sys_get_temp_dir(), uniqid());
        mkdir(Paths::directoryPath($this->projectRoot), 0777, true);

        chdir($this->projectRoot);
    }

    protected function tearDown(): void
    {
        chdir($this->originalWorkingDirectory);
        $this->removeDirectory($this->projectRoot);

        parent::tearDown();
    }

    protected function decodeJson(CommandTester $tester): mixed
    {
        return json_decode(trim($tester->getDisplay()), true);
    }

    /**
     * @param array<string, string> $input
     */
    protected function runCommand(Command $command, array $input): CommandTester
    {
        $tester = new CommandTester($command);
        $tester->execute($input);

        return $tester;
    }

    /**
     * @param callable(SqliteGraph): void $callback
     */
    protected function seed(callable $callback): void
    {
        $graph = new SqliteGraph(Paths::databasePath($this->projectRoot));
        $graph->migrate();

        $callback($graph);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);
        Assert::isArray($entries);

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $entryPath = sprintf('%s/%s', $path, $entry);

            if (is_dir($entryPath)) {
                $this->removeDirectory($entryPath);
                continue;
            }

            unlink($entryPath);
        }

        rmdir($path);
    }
}
