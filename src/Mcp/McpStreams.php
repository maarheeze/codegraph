<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Mcp;

use RuntimeException;

use function fclose;
use function fflush;
use function fgets;
use function fopen;
use function fwrite;

use const PHP_EOL;

final class McpStreams
{
    /**
     * @var resource $stdin
     */
    private $stdin;

    /**
     * @var resource $stdout
     */
    private $stdout;

    /**
     * @var resource|false $stderr
     */
    private $stderr;

    public function __construct()
    {
        $stdin = fopen('php://stdin', 'r');
        $stdout = fopen('php://stdout', 'w');
        $this->stderr = fopen('php://stderr', 'w');

        if ($stdin === false || $stdout === false) {
            throw new RuntimeException('Failed to open stdin/stdout');
        }

        $this->stdin = $stdin;
        $this->stdout = $stdout;
    }

    public function logToStderr(string $message): void
    {
        if ($this->stderr !== false) {
            fwrite($this->stderr, $message . PHP_EOL);
            fflush($this->stderr);
        }
    }

    public function readLine(): ?string
    {
        $line = fgets($this->stdin);

        if ($line === false || $line === '') {
            return null;
        }

        return $line;
    }

    public function writeLine(string $line): void
    {
        fwrite($this->stdout, $line . PHP_EOL);
        fflush($this->stdout);
    }

    public function close(): void
    {
        fclose($this->stdin);
        fclose($this->stdout);

        if ($this->stderr !== false) {
            fclose($this->stderr);
        }
    }
}
