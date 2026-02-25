<?php

declare(strict_types=1);

namespace GetHost\Core\Kernel;

use PDO;
use Throwable;

final class ModuleManager
{
    private string $projectRoot;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = rtrim($projectRoot, '/\\');
    }

    /**
     * @return array<string,array{class:string,enabled:bool}>
     */
    public function loadConfig(): array
    {
        $configPath = $this->projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'modules.php';
        $config = is_file($configPath) ? require $configPath : [];
        if (!is_array($config)) {
            $config = [];
        }

        $pdo = $this->safePdo();
        if (!$pdo || !$this->modulesTableExists($pdo)) {
            return $config;
        }

        try {
            $rows = $pdo->query('SELECT name, enabled FROM modules')->fetchAll();
            foreach ($rows as $row) {
                $name = strtolower(trim((string)($row['name'] ?? '')));
                if ($name === '' || !isset($config[$name])) {
                    continue;
                }
                $config[$name]['enabled'] = ((int)($row['enabled'] ?? 0) === 1);
            }
        } catch (Throwable $e) {
        }

        return $config;
    }

    /**
     * @return array<int,array{name:string,enabled:bool,source:string}>
     */
    public function listStates(): array
    {
        $config = $this->loadConfig();
        $out = [];
        foreach ($config as $name => $row) {
            $out[] = [
                'name' => (string)$name,
                'enabled' => !empty($row['enabled']),
                'source' => 'config_or_db',
            ];
        }
        return $out;
    }

    /**
     * @return array<string,mixed>
     */
    public function setEnabled(string $moduleName, bool $enabled): array
    {
        $moduleName = strtolower(trim($moduleName));
        if ($moduleName === '') {
            return ['ok' => false, 'error' => 'Module name is required'];
        }

        $config = $this->loadConfig();
        if (!isset($config[$moduleName])) {
            return ['ok' => false, 'error' => 'Module not found'];
        }

        $pdo = $this->safePdo();
        if (!$pdo || !$this->modulesTableExists($pdo)) {
            return ['ok' => false, 'error' => "Modules table is not configured. Add table 'modules' first."];
        }

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO modules (name, enabled, updated_at)
                 VALUES (:name, :enabled, NOW())
                 ON DUPLICATE KEY UPDATE enabled = VALUES(enabled), updated_at = NOW()'
            );
            $stmt->execute([
                'name' => $moduleName,
                'enabled' => $enabled ? 1 : 0,
            ]);
            return ['ok' => true, 'name' => $moduleName, 'enabled' => $enabled];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Failed to update module state'];
        }
    }

    private function safePdo(): ?PDO
    {
        try {
            if (!function_exists('app_pdo')) {
                require_once $this->projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
            }
            return function_exists('app_pdo') ? app_pdo() : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function modulesTableExists(PDO $pdo): bool
    {
        try {
            $exists = $pdo->query("SHOW TABLES LIKE 'modules'")->fetchColumn();
            return (bool)$exists;
        } catch (Throwable $e) {
            return false;
        }
    }
}

