<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/services/MonitoringService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_json(['success' => false, 'error' => t('msg_method_not_allowed')], 405);
    exit;
}

$id = (int)($_POST['id'] ?? 0);

try {
    $result = monitor_check_site_by_id($pdo, $id);
    if (!$result['ok']) {
        app_json(['success' => false, 'error' => $result['error']], 422);
        exit;
    }

    app_json(['success' => true, 'status' => $result['status'], 'http_code' => $result['http_code']]);
} catch (Throwable $e) {
    app_json(['success' => false, 'error' => t('request_failed')], 500);
}
