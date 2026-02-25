<?php

declare(strict_types=1);

namespace GetHost\Modules\Monitoring;

use PDO;

final class MonitoringApplicationService
{
    /**
     * @return array<string,mixed>
     */
    public function addSite(PDO $pdo, string $name, string $url): array
    {
        require_once __DIR__ . '/../../../app/services/MonitoringService.php';
        return monitor_add_site($pdo, $name, $url);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getSites(PDO $pdo): array
    {
        require_once __DIR__ . '/../../../app/services/MonitoringService.php';
        return monitor_get_sites($pdo);
    }

    /**
     * @return array<string,mixed>
     */
    public function siteAction(PDO $pdo, int $id, string $action): array
    {
        require_once __DIR__ . '/../../../app/services/MonitoringService.php';
        if ($action === 'activate') {
            return monitor_set_enabled($pdo, $id, true);
        }
        if ($action === 'deactivate') {
            return monitor_set_enabled($pdo, $id, false);
        }
        if ($action === 'delete') {
            return monitor_delete_site($pdo, $id);
        }
        return ['ok' => false, 'error' => 'Unsupported action'];
    }

    /**
     * @return array<string,mixed>
     */
    public function recheck(PDO $pdo, bool $onlyEnabled): array
    {
        require_once __DIR__ . '/../../../app/services/MonitoringService.php';
        return monitor_recheck_all($pdo, $onlyEnabled);
    }
}

