<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/Core/Support/Autoload.php';

use GetHost\Core\Support\Autoload;
use GetHost\Modules\Monitoring\MonitoringApplicationService;

Autoload::init(__DIR__);

try {
    $service = new MonitoringApplicationService();
    $sites = $service->getSites($pdo);
    app_json(['success' => true, 'data' => $sites]);
} catch (Throwable $e) {
    app_json(['success' => false, 'error' => t('load_failed'), 'data' => []], 500);
}
