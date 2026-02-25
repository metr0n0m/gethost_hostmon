<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/Core/Support/Autoload.php';

use GetHost\Core\Support\Autoload;
use GetHost\Modules\Monitoring\MonitoringApplicationService;

Autoload::init(__DIR__);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_json(['success' => false, 'error' => t('msg_method_not_allowed')], 405);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$action = trim((string)($_POST['action'] ?? ''));

try {
    $service = new MonitoringApplicationService();
    $result = $service->siteAction($pdo, $id, $action);
    if (empty($result['ok']) && (($result['error'] ?? '') === 'Unsupported action')) {
        app_json(['success' => false, 'error' => t('msg_action_failed')], 422);
        exit;
    }

    if (!$result['ok']) {
        app_json(['success' => false, 'error' => $result['error']], 422);
        exit;
    }

    app_json(['success' => true]);
} catch (Throwable $e) {
    app_json(['success' => false, 'error' => t('msg_action_failed')], 500);
}
