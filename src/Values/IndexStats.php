<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Values;

final class IndexStats
{
    private int $filesScanned = 0;
    private int $filesChanged = 0;
    private int $filesSkipped = 0;
    private int $filesFailed = 0;
    private int $symbolsEmitted = 0;
    private int $edgesEmitted = 0;
    private int $chunksEmitted = 0;
    private float $durationSeconds = 0.0;
    /** @var array<int, string> */
    private array $errors = [];

    public function addChunks(int $count): void
    {
        $this->chunksEmitted += $count;
    }

    public function addEdges(int $count): void
    {
        $this->edgesEmitted += $count;
    }

    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    public function addSymbols(int $count): void
    {
        $this->symbolsEmitted += $count;
    }

    public function getChunksEmitted(): int
    {
        return $this->chunksEmitted;
    }

    public function getDurationSeconds(): float
    {
        return $this->durationSeconds;
    }

    public function getEdgesEmitted(): int
    {
        return $this->edgesEmitted;
    }

    /**
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFilesFailed(): int
    {
        return $this->filesFailed;
    }

    public function getFilesChanged(): int
    {
        return $this->filesChanged;
    }

    public function getFilesScanned(): int
    {
        return $this->filesScanned;
    }

    public function getFilesSkipped(): int
    {
        return $this->filesSkipped;
    }

    public function getSymbolsEmitted(): int
    {
        return $this->symbolsEmitted;
    }

    public function incrementFilesChanged(): void
    {
        ++$this->filesChanged;
    }

    public function incrementFilesFailed(): void
    {
        ++$this->filesFailed;
    }

    public function incrementFilesScanned(): void
    {
        ++$this->filesScanned;
    }

    public function incrementFilesSkipped(): void
    {
        ++$this->filesSkipped;
    }

    public function setDuration(float $seconds): void
    {
        $this->durationSeconds = $seconds;
    }
}
