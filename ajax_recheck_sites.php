<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/services/MonitoringService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_json(['success' => false, 'error' => t('msg_method_not_allowed')], 405);
    exit;
}

$onlyEnabled = (int)($_POST['only_enabled'] ?? 1) === 1;

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

try {
    $result = monitor_recheck_all($pdo, $onlyEnabled);
    app_json(['success' => true, 'checked' => $result['checked']]);
} catch (Throwable $e) {
    app_json(['success' => false, 'error' => t('msg_recheck_failed')], 500);
}
