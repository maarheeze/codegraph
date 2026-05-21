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
     * @return array<string, callable>
     */
    public function getMcpToolHandlers(): array;
}
