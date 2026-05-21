<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Contracts;

interface Plugin
{
    public function getName(): string;

    public function onBeforeIndex(): void;

    public function onAfterExtraction(): void;

    public function onBeforeResolution(): void;

    public function onAfterResolution(): void;

    public function onAfterIndex(): void;

    /**
     * @return array<int, class-string<FileVisitor>>
     */
    public function getVisitors(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMcpTools(): array;

    /**
     * @param Storage $storage
     * @return array<string, callable(array<string, mixed>): array<string|int, mixed>>
     */
    public function getMcpToolHandlers(Storage $storage): array;
}
