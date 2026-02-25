<?php

declare(strict_types=1);

namespace GetHost\Core\Kernel;

use GetHost\Core\Contracts\ApiModuleInterface;

final class ModuleRegistry
{
    /** @var array<string, ApiModuleInterface> */
    private array $modules = [];

    /**
     * @param array<string, array{class:string,enabled:bool}> $config
     */
    public function __construct(array $config)
    {
        foreach ($config as $name => $item) {
            if (!isset($item['enabled']) || $item['enabled'] !== true) {
                continue;
            }

            if (!isset($item['class']) || !is_string($item['class'])) {
                continue;
            }

            $class = $item['class'];
            if (!class_exists($class)) {
                continue;
            }

            $instance = new $class();
            if (!$instance instanceof ApiModuleInterface) {
                continue;
            }

            $this->modules[strtolower($name)] = $instance;
        }
    }

    public function get(string $name): ?ApiModuleInterface
    {
        $key = strtolower(trim($name));
        return $this->modules[$key] ?? null;
    }

    /**
     * @return string[]
     */
    public function names(): array
    {
        return array_keys($this->modules);
    }
}

