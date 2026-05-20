<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Plugin;

use Maarheeze\CodeGraph\Contracts\Plugin;

final class PluginRegistry
{
    /**
     * @var array<int, Plugin>
     */
    private array $plugins = [];

    public function register(Plugin $plugin): void
    {
        $this->plugins[] = $plugin;
    }

    /**
     * @return array<int, Plugin>
     */
    public function all(): array
    {
        return $this->plugins;
    }

    public function beforeIndex(): void
    {
        foreach ($this->plugins as $plugin) {
            $plugin->onBeforeIndex();
        }
    }

    public function afterExtraction(): void
    {
        foreach ($this->plugins as $plugin) {
            $plugin->onAfterExtraction();
        }
    }

    public function beforeResolution(): void
    {
        foreach ($this->plugins as $plugin) {
            $plugin->onBeforeResolution();
        }
    }

    public function afterResolution(): void
    {
        foreach ($this->plugins as $plugin) {
            $plugin->onAfterResolution();
        }
    }

    public function afterIndex(): void
    {
        foreach ($this->plugins as $plugin) {
            $plugin->onAfterIndex();
        }
    }

    /**
     * @return array<int, class-string>
     */
    public function getVisitors(): array
    {
        $visitors = [];
        foreach ($this->plugins as $plugin) {
            $visitors = [...$visitors, ...$plugin->getVisitors()];
        }

        return $visitors;
    }
}
